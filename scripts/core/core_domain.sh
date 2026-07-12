#!/bin/bash
# ============================================================================
# Core Script: core_domain.sh
# SeederLinux Lite - Ingresso no AD (SSSD/Winbind)
# ============================================================================
# Configura Kerberos, Samba, SSSD/Winbind, PAM, NSS, sudo e mkhomedir para
# ingressar a estacao no dominio Active Directory.
# O metodo de autenticacao e definido por {{AUTH_METHOD}}:
#   sssd     (recomendado, suporta cache offline)
#   winbind  (legado, para compatibilidade)
# Os placeholders {{VARIAVEL}} sao substituidos automaticamente
# pelo sistema na geracao do bundle.
# ============================================================================

set -e

echo "============================================================"
echo "04 - Ingresso no Active Directory"
echo "============================================================"

# ============================================================
# Variaveis
# ============================================================
DOMINIO="{{DOMINIO}}"
DOMINIO_NETBIOS="{{DOMINIO_NETBIOS}}"
DC_IP="{{DC_IP}}"
DC_IP_LIST="{{DC_IP_LIST}}"
OU_PADRAO="{{OU_PADRAO}}"
GRUPO_ADMIN="{{GRUPO_ADMIN}}"
GRUPO_ADMIN_AD="{{GRUPO_ADMIN_AD}}"
GRUPO_ADMIN_LINUX="{{GRUPO_ADMIN_LINUX}}"
GRUPO_DASTI="{{GRUPO_DASTI}}"
OFFLINE_AUTH_ENABLED="{{OFFLINE_AUTH_ENABLED}}"
OFFLINE_AUTH_DAYS="{{OFFLINE_AUTH_DAYS}}"
ADMIN_USERNAME="{{ADMIN_USERNAME}}"

# ============================================================
# Metodo de autenticacao
# ============================================================
AUTH_METHOD="{{AUTH_METHOD}}"
# Fallback para SSSD se nao definido (recomendado para cache offline)
[ -z "$AUTH_METHOD" ] && AUTH_METHOD="sssd"

echo ">>> Dominio: $DOMINIO"
echo ">>> NetBIOS: $DOMINIO_NETBIOS"
echo ">>> DC principal: $DC_IP"
echo ">>> Metodo de autenticacao: $AUTH_METHOD"

# ============================================================
# Validar variaveis obrigatorias
# ============================================================
if [ -z "$DC_IP" ] || [ "$DC_IP" = "{{DC_IP}}" ]; then
    echo "ERRO: DC_IP e obrigatorio. Configure o IP do Controlador de Dominio."
    exit 1
fi

if [ -z "$DOMINIO" ] || [ "$DOMINIO" = "{{DOMINIO}}" ]; then
    echo "ERRO: DOMINIO e obrigatorio. Configure o dominio AD completo."
    exit 1
fi

if [ -z "$DOMINIO_NETBIOS" ] || [ "$DOMINIO_NETBIOS" = "{{DOMINIO_NETBIOS}}" ]; then
    echo "ERRO: DOMINIO_NETBIOS e obrigatorio. Configure o nome NetBIOS do dominio."
    exit 1
fi

# ============================================================
# Configurar Kerberos
# ============================================================
echo ">>> Configurando Kerberos..."
cat > /etc/krb5.conf <<EOF
[libdefaults]
    default_realm = ${DOMINIO_NETBIOS}
    dns_lookup_realm = false
    dns_lookup_kdc = true
    rdns = false
    ticket_lifetime = 24h
    forwardable = yes
    renew_lifetime = 7d

[realms]
    ${DOMINIO_NETBIOS} = {
        kdc = ${DC_IP}
        admin_server = ${DC_IP}
    }

[domain_realm]
    .${DOMINIO} = ${DOMINIO_NETBIOS}
    ${DOMINIO} = ${DOMINIO_NETBIOS}
EOF

echo ">>> Kerberos configurado"

# ============================================================
# Configurar Samba
# ============================================================
echo ">>> Configurando Samba..."

# Determinar winbind offline logon baseado no metodo e cache offline
WINBIND_OFFLINE_LOGON="false"
if [ "$AUTH_METHOD" = "winbind" ] && [ "$OFFLINE_AUTH_ENABLED" = "true" ]; then
    WINBIND_OFFLINE_LOGON="yes"
fi

cat > /etc/samba/smb.conf <<EOF
[global]
    workgroup = ${DOMINIO_NETBIOS}
    realm = ${DOMINIO}
    security = ads
    dns forwarder = ${DC_IP}
    idmap config * : backend = tdb
    idmap config * : range = 3000-7999
    idmap config ${DOMINIO_NETBIOS} : backend = rid
    idmap config ${DOMINIO_NETBIOS} : range = 10000-999999
    template shell = /bin/bash
    template homedir = /home/%D/%U
    winbind use default domain = true
    winbind offline logon = ${WINBIND_OFFLINE_LOGON}
    winbind nss info = rfc2307
    winbind enum users = no
    winbind enum groups = no
    load printers = no
    printing = bsd
    printcap name = /dev/null
    disable spoolss = yes
EOF

echo ">>> Samba configurado"

# ============================================================
# Ingressar no dominio
# ============================================================
echo ">>> Ingressando no dominio..."
# Obter ticket Kerberos (requer senha de admin do dominio)
echo ">>> Solicitando ticket Kerberos..."
kinit "${ADMIN_USERNAME}@${DOMINIO_NETBIOS}" || {
    echo ">>> AVISO: Falha ao obter ticket Kerberos."
    echo ">>> Verifique as credenciais e conectividade com o DC."
    exit 1
}

# Ingressar com net ads join
net ads join -U "${ADMIN_USERNAME}@${DOMINIO_NETBIOS}" \
    createcomputer="${OU_PADRAO}" || {
    echo ">>> ERRO: Falha ao ingressar no dominio"
    exit 1
}
echo ">>> Ingresso no dominio realizado"

# ============================================================
# Configurar SSSD (metodo recomendado)
# ============================================================
if [ "$AUTH_METHOD" = "sssd" ]; then
    echo ">>> Configurando SSSD..."

    OFFLINE_CACHE=""
    if [ "$OFFLINE_AUTH_ENABLED" = "true" ]; then
        echo ">>> Habilitando cache de credenciais offline (SSSD)..."
        DAYS="${OFFLINE_AUTH_DAYS:-3}"
        OFFLINE_CACHE="cache_credentials = true
        krb5_store_password_if_offline = true
        offline_credentials_expiration = ${DAYS}"
    fi

    cat > /etc/sssd/sssd.conf <<EOF
[sssd]
services = nss, pam, sudo
config_file_version = 2
domains = ${DOMINIO}

[domain/${DOMINIO}]
    id_provider = ad
    auth_provider = ad
    access_provider = ad
    ad_domain = ${DOMINIO}
    ad_server = ${DC_IP}
    ad_hostname = $(hostname).${DOMINIO}
    ldap_id_mapping = true
    enumerate = false
    use_fully_qualified_names = false
    fallback_homedir = /home/%d/%u
    default_shell = /bin/bash
    ${OFFLINE_CACHE}
    dyndns_update = false
    sudo_provider = ad
    ldap_sudo_search_base = OU=sudoers,${OU_PADRAO}
EOF

    chmod 600 /etc/sssd/sssd.conf
    echo ">>> SSSD configurado"
fi

# ============================================================
# Configurar Winbind (metodo legado)
# ============================================================
if [ "$AUTH_METHOD" = "winbind" ]; then
    echo ">>> Configurando Winbind..."

    # Configurar PAM para cache de login offline
    if [ "$OFFLINE_AUTH_ENABLED" = "true" ]; then
        echo ">>> Habilitando cache de credenciais offline (Winbind)..."
        if [ -f /etc/security/pam_winbind.conf ]; then
            if grep -q "^[#]*cached_login" /etc/security/pam_winbind.conf; then
                sed -i 's/^#*cached_login.*/cached_login = yes/' /etc/security/pam_winbind.conf
            else
                echo "cached_login = yes" >> /etc/security/pam_winbind.conf
            fi
        fi
    fi

    # Garantir que winbind esta habilitado
    systemctl enable winbind 2>/dev/null || true
    echo ">>> Winbind configurado"
fi

# ============================================================
# Configurar NSS
# ============================================================
echo ">>> Configurando NSS..."
if [ "$AUTH_METHOD" = "sssd" ]; then
    cat > /etc/nsswitch.conf <<EOF
passwd:     files systemd sss
shadow:     files sss
group:      files systemd sss
gshadow:    files

hosts:      files dns

services:   files sss
netgroup:   files sss
sudoers:    files sss

automount:  files sss
EOF
elif [ "$AUTH_METHOD" = "winbind" ]; then
    cat > /etc/nsswitch.conf <<EOF
passwd:     files systemd winbind
shadow:     files winbind
group:      files systemd winbind
gshadow:    files

hosts:      files dns

services:   files

automount:  files
EOF
fi

echo ">>> NSS configurado"

# ============================================================
# Configurar PAM (mkhomedir)
# ============================================================
echo ">>> Configurando PAM e mkhomedir..."
pam-auth-update --enable mkhomedir --force 2>/dev/null || true

# Garantir criacao automatica do home
if [ -f /etc/pam.d/common-session ]; then
    grep -q "pam_mkhomedir" /etc/pam.d/common-session || \
        echo "session required pam_mkhomedir.so skel=/etc/skel umask=0022" >> /etc/pam.d/common-session
fi

echo ">>> PAM configurado"

# ============================================================
# Configurar sudo para grupos do dominio
# ============================================================
echo ">>> Configurando sudo..."
SUDO_FILE="/etc/sudoers.d/seederlinux-domain"
cat > "$SUDO_FILE" <<EOF
# SeederLinux - Acesso sudo para grupos do dominio
%${GRUPO_ADMIN_AD}    ALL=(ALL:ALL) ALL
%${GRUPO_ADMIN_LINUX}  ALL=(ALL:ALL) ALL
EOF

if [ -n "$GRUPO_DASTI" ] && [ "$GRUPO_DASTI" != "" ]; then
    echo "%${GRUPO_DASTI}    ALL=(ALL:ALL) ALL" >> "$SUDO_FILE"
fi

chmod 440 "$SUDO_FILE"
visudo -cf "$SUDO_FILE" || {
    echo ">>> ERRO: sintaxe do sudoers invalida"
    exit 1
}

echo ">>> Sudo configurado"

# ============================================================
# Reiniciar servicos
# ============================================================
echo ">>> Reiniciando servicos..."
systemctl restart samba 2>/dev/null || true

if [ "$AUTH_METHOD" = "sssd" ]; then
    systemctl restart sssd
    systemctl enable sssd
elif [ "$AUTH_METHOD" = "winbind" ]; then
    systemctl restart winbind
    systemctl enable winbind
fi

echo ">>> [04] Ingresso no AD concluido!"
echo "============================================================"
