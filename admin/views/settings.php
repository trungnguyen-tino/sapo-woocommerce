<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Cài đặt</h1>
    <p>Cấu hình kết nối SAPO và tùy chọn đồng bộ</p>
    
    <div id="sapo-settings-root"></div>
</div>

<script type="text/babel">
const { useState, useEffect } = React;

function Toast({ message, type, onClose }) {
    useEffect(() => {
        const timer = setTimeout(onClose, 3000);
        return () => clearTimeout(timer);
    }, []);
    
    return (
        <div className={`sapo-toast ${type === 'success' ? 'sapo-toast-success' : 'sapo-toast-error'}`}>
            <strong>{type === 'success' ? '✓' : '✗'}</strong> {message}
        </div>
    );
}

function SettingsView({ config, onSave, onDisconnect }) {
    const [formData, setFormData] = useState(config);
    const [saved, setSaved] = useState(false);
    
    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(formData);
        setSaved(true);
        setTimeout(() => setSaved(false), 2000);
    };
    
    return (
        <div>
            <h2>Cài đặt</h2>
            
            <form onSubmit={handleSubmit}>
                <div className="sapo-card" style={{marginBottom: '20px'}}>
                    <h3>Kết nối SAPO</h3>
                    <div style={{marginBottom: '15px'}}>
                        <label className="sapo-label">Store</label>
                        <input 
                            type="text" 
                            className="sapo-input" 
                            value={formData.store || ''} 
                            disabled 
                            style={{backgroundColor: '#f3f4f6'}}
                        />
                    </div>
                    <div style={{marginBottom: '15px'}}>
                        <label className="sapo-label">Client ID</label>
                        <input 
                            type="text" 
                            className="sapo-input" 
                            value={formData.client_id || ''} 
                            disabled 
                            style={{backgroundColor: '#f3f4f6'}}
                        />
                    </div>
                    <button type="button" className="sapo-btn sapo-btn-danger" onClick={onDisconnect}>
                        🔌 Ngắt kết nối
                    </button>
                </div>
                
                <div className="sapo-card" style={{marginBottom: '20px'}}>
                    <h3>Đồng bộ tự động</h3>
                    <div style={{marginBottom: '15px'}}>
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer'}}>
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
                            <label className="sapo-label">Tần suất đồng bộ</label>
                            <select 
                                className="sapo-input"
                                value={formData.sync_interval || 900}
                                onChange={(e) => setFormData({...formData, sync_interval: parseInt(e.target.value)})}
                            >
                                <option value="300">5 phút</option>
                                <option value="900">15 phút</option>
                                <option value="1800">30 phút</option>
                                <option value="3600">60 phút</option>
                            </select>
                        </div>
                    )}
                </div>
                
                <div className="sapo-card" style={{marginBottom: '20px'}}>
                    <h3>Tùy chọn đồng bộ</h3>
                    <div style={{display: 'flex', flexDirection: 'column', gap: '12px'}}>
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer'}}>
                            <input 
                                type="checkbox" 
                                checked={formData.update_price || false}
                                onChange={(e) => setFormData({...formData, update_price: e.target.checked})}
                            />
                            <span>💰 Cập nhật giá sản phẩm</span>
                        </label>
                        
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer'}}>
                            <input 
                                type="checkbox" 
                                checked={formData.update_stock || false}
                                onChange={(e) => setFormData({...formData, update_stock: e.target.checked})}
                            />
                            <span>📦 Cập nhật tồn kho</span>
                        </label>
                        
                        <label style={{display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer'}}>
                            <input 
                                type="checkbox" 
                                checked={formData.update_images || false}
                                onChange={(e) => setFormData({...formData, update_images: e.target.checked})}
                            />
                            <span>🖼️ Cập nhật ảnh sản phẩm</span>
                        </label>
                    </div>
                </div>
                
                <div className="sapo-card" style={{marginBottom: '20px'}}>
                    <h3>Debug</h3>
                    <label style={{display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer'}}>
                        <input 
                            type="checkbox" 
                            checked={formData.debug_mode || false}
                            onChange={(e) => setFormData({...formData, debug_mode: e.target.checked})}
                        />
                        <span>🐛 Bật chế độ debug (log chi tiết hơn)</span>
                    </label>
                </div>
                
                <button type="submit" className="sapo-btn sapo-btn-primary" style={{marginRight: '10px'}}>
                    {saved ? '✓ Đã lưu' : '�� Lưu cài đặt'}
                </button>
            </form>
        </div>
    );
}

function SapoSettings() {
    const [loading, setLoading] = useState(false);
    const [config, setConfig] = useState({});
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
        loadConfig();
    }, []);
    
    const loadConfig = async () => {
        setLoading(true);
        try {
            const data = await api('sapo/v1/config');
            setConfig(data);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleSave = async (newConfig) => {
        setLoading(true);
        try {
            await api('sapo/v1/config', {
                method: 'POST',
                body: JSON.stringify(newConfig)
            });
            setConfig(newConfig);
            showToast('Đã lưu cài đặt', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleDisconnect = async () => {
        if (!confirm('Bạn có chắc muốn ngắt kết nối với SAPO?')) return;
        
        setLoading(true);
        try {
            await api('sapo/v1/auth/disconnect', { method: 'POST' });
            showToast('Đã ngắt kết nối', 'success');
            setTimeout(() => {
                window.location.href = 'admin.php?page=sapo-sync';
            }, 1000);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    if (loading && Object.keys(config).length === 0) {
        return <div className="sapo-loading"><div className="sapo-spinner"></div></div>;
    }
    
    return (
        <div className="sapo-admin-app">
            <SettingsView 
                config={config}
                onSave={handleSave}
                onDisconnect={handleDisconnect}
            />
            
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

ReactDOM.render(<SapoSettings />, document.getElementById('sapo-settings-root'));
</script>
