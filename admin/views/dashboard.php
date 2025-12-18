<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="sapo-admin-wrap">
    <div id="sapo-admin-root"></div>
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

function SapoAdmin() {
    const [loading, setLoading] = useState(false);
    const [view, setView] = useState('dashboard');
    const [connected, setConnected] = useState(false);
    const [config, setConfig] = useState({});
    const [products, setProducts] = useState([]);
    const [syncedProducts, setSyncedProducts] = useState([]);
    const [logs, setLogs] = useState([]);
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
    
    useEffect(() => {
        if (view === 'products' && connected) {
            loadProducts();
        } else if (view === 'synced' && connected) {
            loadSyncedProducts();
        } else if (view === 'logs' && connected) {
            loadLogs();
        }
    }, [view, connected]);
    
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
    
    const loadProducts = async () => {
        setLoading(true);
        try {
            const data = await api('sapo/v1/products?limit=50&page=1');
            setProducts(data.products || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const loadSyncedProducts = async () => {
        setLoading(true);
        try {
            const data = await api('sapo/v1/products/synced?limit=50&offset=0');
            setSyncedProducts(data.products || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const loadLogs = async () => {
        setLoading(true);
        try {
            const data = await api('sapo/v1/logs?limit=50&offset=0');
            setLogs(data.logs || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleConnect = async () => {
        try {
            const data = await api('sapo/v1/auth/url', { method: 'POST' });
            window.location.href = data.auth_url;
        } catch (error) {
            showToast(error.message, 'error');
        }
    };
    
    const handleDisconnect = async () => {
        if (!confirm('Bạn có chắc muốn ngắt kết nối với SAPO?')) return;
        
        try {
            await api('sapo/v1/auth/disconnect', { method: 'POST' });
            setConnected(false);
            showToast('Đã ngắt kết nối', 'success');
            loadConfig();
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
    
    const handleSyncProduct = async (productId) => {
        setLoading(true);
        try {
            await api('sapo/v1/products/sync', {
                method: 'POST',
                body: JSON.stringify({ product_ids: [productId] })
            });
            showToast('Đồng bộ thành công', 'success');
            loadSyncedProducts();
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
            <div className="sapo-card">
                <h1 style={{margin: '0 0 20px 0'}}>SAPO WooCommerce Sync</h1>
                
                {!connected ? (
                    <ConnectView config={config} onConnect={handleConnect} onSaveConfig={handleSaveConfig} />
                ) : (
                    <>
                        <div className="sapo-nav">
                            <button className={`sapo-nav-item ${view === 'dashboard' ? 'active' : ''}`} onClick={() => setView('dashboard')}>
                                Dashboard
                            </button>
                            <button className={`sapo-nav-item ${view === 'products' ? 'active' : ''}`} onClick={() => setView('products')}>
                                Sản phẩm SAPO
                            </button>
                            <button className={`sapo-nav-item ${view === 'synced' ? 'active' : ''}`} onClick={() => setView('synced')}>
                                Đã đồng bộ
                            </button>
                            <button className={`sapo-nav-item ${view === 'settings' ? 'active' : ''}`} onClick={() => setView('settings')}>
                                Cài đặt
                            </button>
                            <button className={`sapo-nav-item ${view === 'logs' ? 'active' : ''}`} onClick={() => setView('logs')}>
                                Nhật ký
                            </button>
                        </div>
                        
                        {loading && <div className="sapo-loading"><div className="sapo-spinner"></div></div>}
                        
                        {!loading && view === 'dashboard' && <DashboardView syncStatus={syncStatus} onManualSync={handleManualSync} />}
                        {!loading && view === 'products' && <ProductsView products={products} onSync={handleSyncProduct} />}
                        {!loading && view === 'synced' && <SyncedProductsView products={syncedProducts} onRefresh={loadSyncedProducts} />}
                        {!loading && view === 'settings' && <SettingsView config={config} onSave={handleSaveConfig} onDisconnect={handleDisconnect} />}
                        {!loading && view === 'logs' && <LogsView logs={logs} />}
                    </>
                )}
            </div>
            
            {toast && <Toast message={toast.message} type={toast.type} onClose={() => setToast(null)} />}
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
                    <h3 style={{margin: '0 0 10px 0', color: '#6b7280'}}>Tổng sản phẩm</h3>
                    <div style={{fontSize: '32px', fontWeight: 'bold', color: '#2563eb'}}>
                        {syncStatus.total_synced || 0}
                    </div>
                </div>
                
                <div className="sapo-card">
                    <h3 style={{margin: '0 0 10px 0', color: '#6b7280'}}>Lần đồng bộ cuối</h3>
                    <div style={{fontSize: '16px', fontWeight: '500'}}>
                        {syncStatus.last_sync_formatted || 'Chưa đồng bộ'}
                    </div>
                </div>
                
                <div className="sapo-card">
                    <h3 style={{margin: '0 0 10px 0', color: '#6b7280'}}>Trạng thái</h3>
                    <div>
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
                    🔄 Đồng bộ ngay
                </button>
            </div>
        </div>
    );
}

function ProductsView({ products, onSync }) {
    if (products.length === 0) {
        return <div style={{textAlign: 'center', padding: '40px', color: '#6b7280'}}>Không có sản phẩm nào</div>;
    }
    
    return (
        <div>
            <h2>Sản phẩm từ SAPO</h2>
            <table className="sapo-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên sản phẩm</th>
                        <th>SKU</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    {products.map(product => (
                        <tr key={product.id}>
                            <td>{product.id}</td>
                            <td>{product.name}</td>
                            <td>{product.variants?.[0]?.sku || '-'}</td>
                            <td>{product.variants?.[0]?.price ? new Intl.NumberFormat('vi-VN').format(product.variants[0].price) + ' ₫' : '-'}</td>
                            <td>{product.variants?.[0]?.inventory_quantity || 0}</td>
                            <td>
                                <button className="sapo-btn sapo-btn-primary" style={{padding: '6px 12px', fontSize: '13px'}} onClick={() => onSync(product.id)}>
                                    Đồng bộ
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function SyncedProductsView({ products, onRefresh }) {
    if (products.length === 0) {
        return <div style={{textAlign: 'center', padding: '40px', color: '#6b7280'}}>Chưa có sản phẩm đã đồng bộ</div>;
    }
    
    return (
        <div>
            <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px'}}>
                <h2 style={{margin: 0}}>Sản phẩm đã đồng bộ</h2>
                <button className="sapo-btn sapo-btn-secondary" onClick={onRefresh}>Làm mới</button>
            </div>
            
            <table className="sapo-table">
                <thead>
                    <tr>
                        <th>SAPO ID</th>
                        <th>WC ID</th>
                        <th>Loại</th>
                        <th>Đồng bộ lần cuối</th>
                    </tr>
                </thead>
                <tbody>
                    {products.map(product => (
                        <tr key={product.id}>
                            <td>{product.sapo_product_id}</td>
                            <td>{product.wc_product_id}</td>
                            <td><span className="sapo-badge sapo-badge-info">{product.product_type}</span></td>
                            <td>{product.last_synced}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function SettingsView({ config, onSave, onDisconnect }) {
    const [formData, setFormData] = useState(config);
    
    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(formData);
    };
    
    return (
        <div>
            <h2>Cài đặt</h2>
            
            <form onSubmit={handleSubmit}>
                <div style={{marginBottom: '30px'}}>
                    <h3>Kết nối SAPO</h3>
                    <div style={{marginBottom: '15px'}}>
                        <label className="sapo-label">Store</label>
                        <input type="text" className="sapo-input" value={formData.store || ''} disabled />
                    </div>
                    <button type="button" className="sapo-btn sapo-btn-danger" onClick={onDisconnect}>
                        Ngắt kết nối
                    </button>
                </div>
                
                <div style={{marginBottom: '30px'}}>
                    <h3>Đồng bộ tự động</h3>
                    <div style={{marginBottom: '15px'}}>
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px'}}>
                            <input 
                                type="checkbox" 
                                checked={formData.auto_inventory || false}
                                onChange={(e) => setFormData({...formData, auto_inventory: e.target.checked})}
                            />
                            <span>Bật đồng bộ tồn kho tự động</span>
                        </label>
                    </div>
                    
                    {formData.auto_inventory && (
                        <div style={{marginBottom: '15px'}}>
                            <label className="sapo-label">Tần suất (giây)</label>
                            <input 
                                type="number" 
                                className="sapo-input"
                                value={formData.sync_interval || 900}
                                onChange={(e) => setFormData({...formData, sync_interval: parseInt(e.target.value)})}
                            />
                        </div>
                    )}
                </div>
                
                <div style={{marginBottom: '30px'}}>
                    <h3>Tùy chọn đồng bộ</h3>
                    <div style={{display: 'flex', flexDirection: 'column', gap: '10px'}}>
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px'}}>
                            <input 
                                type="checkbox" 
                                checked={formData.update_price || false}
                                onChange={(e) => setFormData({...formData, update_price: e.target.checked})}
                            />
                            <span>Cập nhật giá</span>
                        </label>
                        
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px'}}>
                            <input 
                                type="checkbox" 
                                checked={formData.update_stock || false}
                                onChange={(e) => setFormData({...formData, update_stock: e.target.checked})}
                            />
                            <span>Cập nhật tồn kho</span>
                        </label>
                        
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px'}}>
                            <input 
                                type="checkbox" 
                                checked={formData.update_images || false}
                                onChange={(e) => setFormData({...formData, update_images: e.target.checked})}
                            />
                            <span>Cập nhật hình ảnh</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" className="sapo-btn sapo-btn-primary">
                    Lưu cài đặt
                </button>
            </form>
        </div>
    );
}

function LogsView({ logs }) {
    if (logs.length === 0) {
        return <div style={{textAlign: 'center', padding: '40px', color: '#6b7280'}}>Chưa có nhật ký nào</div>;
    }
    
    return (
        <div>
            <h2>Nhật ký đồng bộ</h2>
            <table className="sapo-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Loại</th>
                        <th>Hành động</th>
                        <th>Trạng thái</th>
                        <th>Thông báo</th>
                    </tr>
                </thead>
                <tbody>
                    {logs.map(log => (
                        <tr key={log.id}>
                            <td>{log.created_at}</td>
                            <td>{log.log_type}</td>
                            <td>{log.action}</td>
                            <td>
                                <span className={`sapo-badge ${log.status === 'success' ? 'sapo-badge-success' : 'sapo-badge-error'}`}>
                                    {log.status}
                                </span>
                            </td>
                            <td>{log.message}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

ReactDOM.render(<SapoAdmin />, document.getElementById('sapo-admin-root'));
</script>
