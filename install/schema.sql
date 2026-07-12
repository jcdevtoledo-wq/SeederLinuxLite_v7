-- ============================================================================
-- SeederLinux Lite - Canonical Database Schema (PostgreSQL 16+)
-- ============================================================================
-- This is the ONE schema file. It is idempotent: safe to re-run.
-- All table names match what the PHP application (api/index.php) expects.
-- Core script content is loaded separately by insert_core_scripts.sql.
-- ============================================================================

-- ============================================================================
-- Table: users
-- ============================================================================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(150),
    role VARCHAR(20) DEFAULT 'operador_om',
    organization_id INTEGER REFERENCES organizations(id) ON DELETE SET NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin user (password: admin123, bcrypt cost=12)
INSERT INTO users (username, password_hash, email, full_name, role, is_active)
VALUES ('admin', '$2y$12$aclfbpmKYX0DoMcu8EmQeO1xyziOBv9/WjuWR6y3/ovgF74QTaLhC', 'admin@seeder.local', 'Administrator', 'admin_gap', TRUE)
ON CONFLICT (username) DO NOTHING;

-- ============================================================================
-- Table: user_tokens
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_user_tokens_user ON user_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_user_tokens_expires ON user_tokens(expires_at);

-- ============================================================================
-- Table: organizations
-- ============================================================================
CREATE TABLE IF NOT EXISTS organizations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    acronym VARCHAR(20) UNIQUE NOT NULL,
    domain VARCHAR(100),
    description TEXT,
    serial_config INTEGER DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample organization
INSERT INTO organizations (name, acronym, domain, description)
VALUES ('Comando da Comara', 'COMARA', 'comara.intraer', 'Comando do Comando da Aeronautica de Brasilia')
ON CONFLICT (acronym) DO NOTHING;

-- ============================================================================
-- Table: variable_definitions
-- ============================================================================
CREATE TABLE IF NOT EXISTS variable_definitions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    placeholder VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    type VARCHAR(20) DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',
    is_required BOOLEAN DEFAULT TRUE,
    default_value TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_var_defs_type ON variable_definitions(type);
CREATE INDEX IF NOT EXISTS idx_var_defs_category ON variable_definitions(category);

-- ============================================================================
-- Variable catalog (56 definitions)
-- ============================================================================
INSERT INTO variable_definitions (name, placeholder, description, type, category, is_required, default_value, display_order) VALUES
-- Domain Configuration
('DOMINIO', '{{DOMINIO}}', 'Dominio AD completo', 'domain', 'dominio', TRUE, 'comara.intraer', 1),
('DOMINIO_NETBIOS', '{{DOMINIO_NETBIOS}}', 'Nome NetBIOS do dominio', 'netbios', 'dominio', TRUE, 'COMARA', 2),
('DC_IP', '{{DC_IP}}', 'IP do Controlador de Dominio', 'ip', 'dominio', TRUE, '10.108.64.51', 3),
('DC_SECUNDARIO_IP', '{{DC_SECUNDARIO_IP}}', 'IP do Controlador de Dominio secundario', 'ip', 'dominio', FALSE, '', 4),
('DC_IP_LIST', '{{DC_IP_LIST}}', 'Lista de IPs de todos os Controladores de Dominio (separados por virgula)', 'array', 'dominio', FALSE, '', 5),
('DNS_INTERNET', '{{DNS_INTERNET}}', 'DNS para internet (fallback)', 'ip', 'rede', TRUE, '10.108.64.27', 6),
('DNS_PRIMARIO', '{{DNS_PRIMARIO}}', 'DNS primario para resolucao de nomes', 'ip', 'dominio', TRUE, '10.108.64.51', 7),
('DNS_SECUNDARIO', '{{DNS_SECUNDARIO}}', 'DNS secundario (fallback)', 'ip', 'dominio', FALSE, '10.108.64.27', 8),
('NTP_SERVER', '{{NTP_SERVER}}', 'Servidor NTP para sincronizacao de horario', 'ip', 'dominio', FALSE, '10.108.64.51', 9),
('OU_PADRAO', '{{OU_PADRAO}}', 'Unidade Organizacional padrao no AD', 'string', 'dominio', FALSE, 'OU=Estacoes,DC=comara,DC=intraer', 10),
('GRUPO_ADMIN', '{{GRUPO_ADMIN}}', 'Grupo administrador do dominio', 'string', 'dominio', TRUE, 'Domain Admins', 11),
('AUTH_METHOD', '{{AUTH_METHOD}}', 'Metodo de autenticacao: sssd (recomendado para cache offline) ou winbind (legado)', 'select', 'dominio', FALSE, 'sssd', 12),
('ADMIN_USERNAME', '{{ADMIN_USERNAME}}', 'Nome de usuario administrador do dominio para realizar o ingresso', 'string', 'dominio', FALSE, 'Administrator', 13),
('OFFLINE_AUTH_ENABLED', '{{OFFLINE_AUTH_ENABLED}}', 'Habilitar autenticacao offline', 'boolean', 'dominio', FALSE, 'true', 14),
('OFFLINE_AUTH_DAYS', '{{OFFLINE_AUTH_DAYS}}', 'Dias para cache de credenciais offline', 'string', 'dominio', FALSE, '30', 15),

-- Repository URLs
('BASE_URL', '{{BASE_URL}}', 'URL base do repositorio de scripts', 'url', 'rede', TRUE, 'https://softwarelivre.comara.intraer', 20),
('REPOSITORY_MODE', '{{REPOSITORY_MODE}}', 'Modo de repositorio: PUBLIC, MIRROR, HYBRID, CUSTOM', 'select', 'repositorios', TRUE, 'MIRROR', 21),
('REPOSITORY_URL', '{{REPOSITORY_URL}}', 'URL do repositorio espelho', 'url', 'repositorios', FALSE, 'https://softwarelivre.comara.intraer', 22),
('REPOSITORY_FALLBACK', '{{REPOSITORY_FALLBACK}}', 'URL de repositorio fallback (internet)', 'url', 'repositorios', FALSE, 'http://deb.debian.org/debian', 23),

-- OCS Inventory
('OCS_SERVER', '{{OCS_SERVER}}', 'Servidor OCS Inventory', 'url', 'inventario', TRUE, 'http://ocs.comara.intraer/ocsinventory', 30),
('OCS_TAG', '{{OCS_TAG}}', 'Tag OCS da organizacao', 'string', 'inventario', TRUE, 'GAPBE-COMARA', 31),
('GLPI_SERVER', '{{GLPI_SERVER}}', 'Servidor GLPI para inventario', 'url', 'inventario', FALSE, '', 32),
('INVENTORY_ENABLED', '{{INVENTORY_ENABLED}}', 'Habilitar inventario automatico', 'boolean', 'inventario', FALSE, 'true', 33),

-- Print Server
('PRINT_SERVER', '{{PRINT_SERVER}}', 'Servidor de impressao', 'ip', 'rede', FALSE, '10.108.64.20', 40),
('DEFAULT_PRINTER', '{{DEFAULT_PRINTER}}', 'Impressora padrao', 'string', 'impressoras', FALSE, '', 41),
('PRINTERS', '{{PRINTERS}}', 'Lista de impressoras (separadas por virgula)', 'array', 'impressoras', FALSE, '', 42),

-- Proxy Configuration
('PROXY_HTTP', '{{PROXY_HTTP}}', 'Proxy HTTP corporativo', 'ip', 'proxy', FALSE, '10.108.88.4', 50),
('PROXY_PORTA', '{{PROXY_PORTA}}', 'Porta do proxy', 'port', 'proxy', FALSE, '8080', 51),
('PROXY_URL', '{{PROXY_URL}}', 'URL completa do proxy', 'url', 'proxy', FALSE, 'http://proxy.comara.intraer:8080', 52),
('PROXY_MODE', '{{PROXY_MODE}}', 'Modo de proxy: NONE, MANUAL, PAC', 'select', 'navegador', FALSE, 'MANUAL', 53),
('PAC_URL', '{{PAC_URL}}', 'URL do arquivo PAC (Proxy Auto-Config)', 'url', 'navegador', FALSE, '', 54),
('NO_PROXY', '{{NO_PROXY}}', 'Lista de excecoes de proxy (separadas por virgula)', 'array', 'navegador', FALSE, 'localhost,127.0.0.1,comara.intraer', 55),

-- Browser Configuration
('HOMEPAGE', '{{HOMEPAGE}}', 'Pagina inicial do portal', 'url', 'navegador', FALSE, 'www.comara.intraer', 60),

-- Admin Groups
('GRUPO_ADMIN_AD', '{{GRUPO_ADMIN_AD}}', 'Grupo admin no AD para sudo', 'string', 'seguranca', TRUE, 'Dominio\ Admins', 70),
('GRUPO_ADMIN_LINUX', '{{GRUPO_ADMIN_LINUX}}', 'Grupo local para sudo', 'string', 'seguranca', TRUE, 'linux-admins', 71),
('GRUPO_DASTI', '{{GRUPO_DASTI}}', 'Grupo DASTI para sudo', 'string', 'seguranca', FALSE, '_DASTI', 72),

-- Branding
('OM_ACRONYM', '{{OM_ACRONYM}}', 'Sigla da Organizacao Militar', 'string', 'branding', FALSE, 'COMARA', 80),
('OM_NAME', '{{OM_NAME}}', 'Nome completo da Organizacao Militar', 'string', 'identidade', FALSE, '', 81),
('DISPLAY_NAME', '{{DISPLAY_NAME}}', 'Nome de exibicao da OM', 'string', 'branding', FALSE, 'Comando da Comara', 82),
('WALLPAPER_URL', '{{WALLPAPER_URL}}', 'URL do wallpaper da OM', 'url', 'branding', FALSE, 'https://softwarelivre.comara.intraer/wallpapers/comara.jpg', 83),
('WALLPAPER_LOGIN_URL', '{{WALLPAPER_LOGIN_URL}}', 'URL do wallpaper da tela de login', 'url', 'branding', FALSE, '', 84),
('LOGO_URL', '{{LOGO_URL}}', 'URL do logo da OM', 'url', 'branding', FALSE, 'https://softwarelivre.comara.intraer/logos/comara.png', 85),
('GREETER_URL', '{{GREETER_URL}}', 'URL do greeter personalizado', 'url', 'branding', FALSE, '', 86),
('THEME', '{{THEME}}', 'Tema GTK a ser aplicado', 'string', 'branding', FALSE, 'Adwaita', 87),
('CONKY_PROFILE', '{{CONKY_PROFILE}}', 'Perfil do Conky para monitoracao', 'string', 'branding', FALSE, 'default', 88),

-- Desktop Environment
('DESKTOP_ENV', '{{DESKTOP_ENV}}', 'Ambiente grafico: cinnamon, mate, gnome, xfce, kde, lxde', 'select', 'ambiente', FALSE, 'cinnamon', 90),
('DISPLAY_MANAGER', '{{DISPLAY_MANAGER}}', 'Gerenciador de sessao: lightdm, gdm3, sddm', 'select', 'ambiente', FALSE, 'lightdm', 91),

-- File Server
('SERVIDOR_ARQUIVOS', '{{SERVIDOR_ARQUIVOS}}', 'Servidor de arquivos (SMB/NFS)', 'ip', 'arquivos', FALSE, '10.108.64.20', 100),
('COMPARTILHAMENTOS', '{{COMPARTILHAMENTOS}}', 'Lista de compartilhamentos (separados por virgula)', 'array', 'arquivos', FALSE, 'publico,usuarios,setores', 101),
('MOUNT_BASE', '{{MOUNT_BASE}}', 'Base de montagem para compartilhamentos', 'string', 'arquivos', FALSE, '/mnt/servidor', 102),

-- Applications
('INSTALL_APPS', '{{INSTALL_APPS}}', 'Instalar OnlyOffice, Google Chrome e Firefox ESR?', 'boolean', 'aplicacoes', FALSE, 'true', 110),
('INSTALL_LEGADOS', '{{INSTALL_LEGADOS}}', 'Instalar Java 8 e Firefox 52.7 ESR para sistemas legados?', 'boolean', 'aplicacoes', FALSE, 'false', 111),

-- Remote Access
('REMOTE_METHOD', '{{REMOTE_METHOD}}', 'Metodo de acesso remoto (ssh, xrdp, anydesk)', 'select', 'acesso_remoto', FALSE, 'ssh', 120),
('REMOTE_SERVER', '{{REMOTE_SERVER}}', 'Servidor de acesso remoto', 'string', 'acesso_remoto', FALSE, '', 121),
('VNC_ENABLED', '{{VNC_ENABLED}}', 'Habilitar servidor VNC (x11vnc)?', 'boolean', 'acesso_remoto', FALSE, 'false', 122),
('VNC_PASSWORD', '{{VNC_PASSWORD}}', 'Senha do servidor VNC (em branco = aleatoria)', 'password', 'acesso_remoto', FALSE, '', 123),

-- Certificates
('CERTIFICATE_BUNDLE', '{{CERTIFICATE_BUNDLE}}', 'URL do bundle de certificados CA', 'url', 'certificados', FALSE, '', 130),
('CERTIFICATE_AUTO_INSTALL', '{{CERTIFICATE_AUTO_INSTALL}}', 'Instalar certificados automaticamente', 'boolean', 'certificados', FALSE, 'true', 131)
ON CONFLICT (name) DO NOTHING;

-- ============================================================================
-- Table: organization_variables
-- ============================================================================
CREATE TABLE IF NOT EXISTS organization_variables (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    variable_id INTEGER NOT NULL REFERENCES variable_definitions(id) ON DELETE CASCADE,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(organization_id, variable_id)
);

CREATE INDEX IF NOT EXISTS idx_org_vars_org ON organization_variables(organization_id);
CREATE INDEX IF NOT EXISTS idx_org_vars_var ON organization_variables(variable_id);

-- Insert default values for COMARA (org id=1) for all variables
INSERT INTO organization_variables (organization_id, variable_id, value)
SELECT 1, id, COALESCE(default_value, '') FROM variable_definitions
ON CONFLICT (organization_id, variable_id) DO NOTHING;

-- Set OM_ACRONYM for COMARA
UPDATE organization_variables SET value = 'COMARA'
WHERE organization_id = 1 AND variable_id = (SELECT id FROM variable_definitions WHERE name = 'OM_ACRONYM');

-- ============================================================================
-- Table: scripts
-- ============================================================================
-- Core script content is loaded by insert_core_scripts.sql (run after schema).
-- This table definition must match what the PHP app and insert_core_scripts.sql expect.
CREATE TABLE IF NOT EXISTS scripts (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    filename VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    content TEXT NOT NULL DEFAULT '',
    is_core BOOLEAN DEFAULT FALSE,
    execution_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    version INTEGER DEFAULT 1,
    organization_id INTEGER REFERENCES organizations(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_scripts_filename ON scripts(filename);
CREATE INDEX IF NOT EXISTS idx_scripts_core ON scripts(is_core, execution_order);

-- ============================================================================
-- Table: deploy_bundles
-- ============================================================================
CREATE TABLE IF NOT EXISTS deploy_bundles (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    filename VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    script_ids TEXT,
    scripts_count INTEGER DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_deploy_bundles_org ON deploy_bundles(organization_id);
CREATE INDEX IF NOT EXISTS idx_deploy_bundles_date ON deploy_bundles(generated_at DESC);

-- ============================================================================
-- Table: stations
-- ============================================================================
CREATE TABLE IF NOT EXISTS stations (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER REFERENCES organizations(id) ON DELETE SET NULL,
    hostname VARCHAR(200),
    ip_address VARCHAR(45),
    mac_address VARCHAR(17),
    os_name VARCHAR(100),
    os_version VARCHAR(100),
    serial_number VARCHAR(100),
    last_checkin TIMESTAMP,
    status VARCHAR(20) DEFAULT 'never_connected',
    configuration_serial INTEGER DEFAULT 0,
    token TEXT UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_stations_org ON stations(organization_id);
CREATE INDEX IF NOT EXISTS idx_stations_token ON stations(token);
CREATE INDEX IF NOT EXISTS idx_stations_status ON stations(status);
CREATE INDEX IF NOT EXISTS idx_stations_checkin ON stations(last_checkin DESC);

-- ============================================================================
-- Table: audit_events
-- ============================================================================
CREATE TABLE IF NOT EXISTS audit_events (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER REFERENCES organizations(id) ON DELETE SET NULL,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INTEGER,
    action VARCHAR(50) NOT NULL,
    details JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_audit_events_org ON audit_events(organization_id);
CREATE INDEX IF NOT EXISTS idx_audit_events_user ON audit_events(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_events_entity ON audit_events(entity, entity_id);
CREATE INDEX IF NOT EXISTS idx_audit_events_date ON audit_events(created_at DESC);

-- ============================================================================
-- Table: system_settings
-- ============================================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    value_type VARCHAR(20) DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO system_settings (key, value, value_type, description, is_public) VALUES
('app_name', 'SeederLinux Lite', 'string', 'Nome da aplicacao', TRUE),
('app_version', '1.0.0', 'string', 'Versao do sistema', TRUE),
('require_https', 'false', 'boolean', 'Requer HTTPS para acesso', FALSE),
('max_login_attempts', '5', 'integer', 'Maximo de tentativas de login', FALSE),
('login_lockout_minutes', '15', 'integer', 'Minutos de bloqueio apos tentativas', FALSE),
('session_timeout', '86400', 'integer', 'Timeout de sessao em segundos', FALSE),
('bundle_retention_days', '30', 'integer', 'Dias para reter bundles gerados', FALSE),
('max_bundle_downloads', '100', 'integer', 'Maximo de downloads por hora por OM', FALSE),
('enable_activity_log', 'true', 'boolean', 'Habilitar log de atividades', FALSE),
('default_timezone', 'America/Sao_Paulo', 'string', 'Fuso horario padrao', TRUE)
ON CONFLICT (key) DO NOTHING;

-- ============================================================================
-- View: v_organization_variables
-- ============================================================================
CREATE OR REPLACE VIEW v_organization_variables AS
SELECT
    o.id AS org_id,
    o.acronym AS org_acronym,
    o.name AS org_name,
    vd.id AS var_id,
    vd.name AS var_name,
    vd.placeholder,
    vd.description,
    vd.category,
    vd.default_value,
    COALESCE(ov.value, vd.default_value) AS current_value
FROM organizations o
CROSS JOIN variable_definitions vd
LEFT JOIN organization_variables ov ON ov.organization_id = o.id AND ov.variable_id = vd.id
WHERE o.is_active = TRUE
ORDER BY vd.display_order;

-- ============================================================================
-- Grant permissions to seeder user
-- ============================================================================
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO seeder;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO seeder;

-- ============================================================================
-- End of Schema
-- ============================================================================
