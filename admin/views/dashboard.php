<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>SAPO WooCommerce Sync</h1>
    
    <div id="sapo-dashboard-root"></div>
</div>

<script type="text/babel">
const { useState, useEffect } = React;

function Toast({ message, type, onClose }) {
    useEffect(() => {
        const timer = setTimeout(onClose, 3000);
        return () => clearTimeout(timer);
    }, []);
    
    const className = type === 'success' ? 'sapo-toast-success' : 'sapo-toast-error';
    
    return (
        <div className={`sapo-toast ${className}`}>
            <strong>{type === 'success' ? '✓' : '✗'}</strong> {message}
        </div>
    );
}

function ConnectView({ config, onConnect, onSaveConfig }) {
    const [formData, setFormData] = useState({
        store: config.store || '',
        client_id: config.client_id || '',
        client_secret: ''
    });
    
    const handleSubmit = (e) => {
        e.preventDefault();
        onSaveConfig(formData);
        setTimeout(() => {
            onConnect();
        }, 500);
    };
    
    return (
        <div style={{maxWidth: '600px', margin: '0 auto', padding: '40px 0'}}>
            <h2>Kết nối với SAPO</h2>
            <p>Nhập thông tin để kết nối với tài khoản SAPO của bạn</p>
            
            <form onSubmit={handleSubmit} style={{marginTop: '30px'}}>
                <div style={{marginBottom: '20px'}}>
                    <label className="sapo-label">Store Name *</label>
                    <input 
                        type="text"
                        className="sapo-input"
                        placeholder="tenant.mysapo.net"
                        value={formData.store}
                        onChange={(e) => setFormData({...formData, store: e.target.value})}
                        required
                    />
                    <small style={{display: 'block', marginTop: '5px', color: '#6b7280'}}>
                        Ví dụ: mystore.mysapo.net
                    </small>
                </div>
                
                <div style={{marginBottom: '20px'}}>
                    <label className="sapo-label">Client ID *</label>
                    <input 
                        type="text"
                        className="sapo-input"
                        value={formData.client_id}
                        onChange={(e) => setFormData({...formData, client_id: e.target.value})}
                        required
                    />
                </div>
                
                <div style={{marginBottom: '20px'}}>
                    <label className="sapo-label">Client Secret *</label>
                    <input 
                        type="password"
                        className="sapo-input"
                        value={formData.client_secret}
                        onChange={(e) => setFormData({...formData, client_secret: e.target.value})}
                        required
                    />
                </div>
                
                <button type="submit" className="sapo-btn sapo-btn-primary" style={{width: '100%'}}>
                    Kết nối SAPO
                </button>
            </form>
        </div>
    );
}

function DashboardView({ syncStatus, onManualSync }) {
    return (
        <div>
            <div className="sapo-grid sapo-grid-3" style={{marginBottom: '30px'}}>
                <div className="sapo-card">
                    <h3 style={{margin: '0 0 10px 0', color: '#6b7280', fontSize: '14px'}}>Tổng sản phẩm đã đồng bộ</h3>
                    <div style={{fontSize: '32px', fontWeight: 'bold', color: '#2563eb'}}>
                        {syncStatus.total_synced || 0}
                    </div>
                </div>
                
                <div className="sapo-card">
                    <h3 style={{margin: '0 0 10px 0', color: '#6b7280', fontSize: '14px'}}>Lần đồng bộ cuối</h3>
                    <div style={{fontSize: '16px', fontWeight: '500'}}>
                        {syncStatus.last_sync_formatted || 'Chưa đồng bộ'}
                    </div>
                </div>
                
                <div className="sapo-card">
                    <h3 style={{margin: '0 0 10px 0', color: '#6b7280', fontSize: '14px'}}>Trạng thái</h3>
                    <div style={{marginTop: '5px'}}>
                        {syncStatus.is_running ? (
                            <span className="sapo-badge sapo-badge-info">Đang đồng bộ</span>
                        ) : (
                            <span className="sapo-badge sapo-badge-success">Sẵn sàng</span>
                        )}
                    </div>
                </div>
            </div>
            
            <div style={{textAlign: 'center', padding: '40px'}}>
                <button 
                    className="sapo-btn sapo-btn-primary" 
                    onClick={onManualSync}
                    disabled={syncStatus.is_running}
                    style={{fontSize: '16px', padding: '14px 32px'}}
                >
                    {syncStatus.is_running ? '⏳ Đang đồng bộ...' : '🔄 Đồng bộ ngay'}
                </button>
                <p style={{marginTop: '15px', color: '#6b7280', fontSize: '14px'}}>
                    Cập nhật tồn kho cho tất cả sản phẩm đã đồng bộ
                </p>
            </div>
        </div>
    );
}

function SapoDashboard() {
    const [loading, setLoading] = useState(false);
    const [connected, setConnected] = useState(false);
    const [config, setConfig] = useState({});
    const [syncStatus, setSyncStatus] = useState({});
    const [toast, setToast] = useState(null);
    
    const showToast = (message, type = 'success') => {
        setToast({ message, type });
    };
    
    const api = async (endpoint, options = {}) => {
        const response = await fetch(wpApiSettings.root + endpoint, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpApiSettings.nonce,
                ...options.headers
            }
        });
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'API Error');
        }
        
        return response.json();
    };
    
    useEffect(() => {
        loadAuthStatus();
        loadConfig();
        loadSyncStatus();
    }, []);
    
    const loadAuthStatus = async () => {
        try {
            const data = await api('sapo/v1/auth/status');
            setConnected(data.connected);
        } catch (error) {
            console.error('Error loading auth status:', error);
        }
    };
    
    const loadConfig = async () => {
        try {
            const data = await api('sapo/v1/config');
            setConfig(data);
            setConnected(data.connected);
        } catch (error) {
            console.error('Error loading config:', error);
        }
    };
    
    const loadSyncStatus = async () => {
        try {
            const data = await api('sapo/v1/sync/status');
            setSyncStatus(data);
        } catch (error) {
            console.error('Error loading sync status:', error);
        }
    };
    
    const handleConnect = async () => {
        try {
            const data = await api('sapo/v1/auth/url', { method: 'POST' });
            window.location.href = data.auth_url;
        } catch (error) {
            showToast(error.message, 'error');
        }
    };
    
    const handleSaveConfig = async (newConfig) => {
        setLoading(true);
        try {
            await api('sapo/v1/config', {
                method: 'POST',
                body: JSON.stringify(newConfig)
            });
            setConfig(newConfig);
            showToast('Đã lưu cấu hình', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleManualSync = async () => {
        setLoading(true);
        try {
            const result = await api('sapo/v1/sync/inventory', { method: 'POST' });
            showToast(`Đã cập nhật ${result.updated} sản phẩm`, 'success');
            loadSyncStatus();
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    return (
        <div className="sapo-admin-app">
            {!connected ? (
                <ConnectView 
                    config={config} 
                    onConnect={handleConnect} 
                    onSaveConfig={handleSaveConfig} 
                />
            ) : (
                <>
                    <DashboardView 
                        syncStatus={syncStatus} 
                        onManualSync={handleManualSync} 
                    />
                    
                    <div className="sapo-card" style={{marginTop: '20px'}}>
                        <h3>Quick Links</h3>
                        <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '10px'}}>
                            <a href="admin.php?page=sapo-products" className="sapo-btn sapo-btn-secondary">
                                📦 Sản phẩm SAPO
                            </a>
                            <a href="admin.php?page=sapo-synced" className="sapo-btn sapo-btn-secondary">
                                ✅ Đã đồng bộ
                            </a>
                            <a href="admin.php?page=sapo-categories" className="sapo-btn sapo-btn-secondary">
                                📂 Categories
                            </a>
                            <a href="admin.php?page=sapo-attributes" className="sapo-btn sapo-btn-secondary">
                                🏷️ Attributes
                            </a>
                            <a href="admin.php?page=sapo-settings" className="sapo-btn sapo-btn-secondary">
                                ⚙️ Cài đặt
                            </a>
                            <a href="admin.php?page=sapo-logs" className="sapo-btn sapo-btn-secondary">
                                📝 Nhật ký
                            </a>
                        </div>
                    </div>
                </>
            )}
            
            {toast && (
                <Toast 
                    message={toast.message} 
                    type={toast.type} 
                    onClose={() => setToast(null)} 
                />
            )}
        </div>
    );
}

ReactDOM.render(<SapoDashboard />, document.getElementById('sapo-dashboard-root'));
</script>
