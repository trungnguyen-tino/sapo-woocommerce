<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Category Mapping</h1>
    <p>Mapping SAPO Collections sang WooCommerce Categories</p>
    
    <div id="sapo-categories-root"></div>
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

function CategoryMappingView({ mappings, sapoCollections, wcCategories, onSave, onDelete, onRefresh }) {
    const [showAddForm, setShowAddForm] = useState(false);
    const [formData, setFormData] = useState({
        sapo_collection_id: '',
        wc_category_id: '',
        auto_create: false
    });
    
    const handleSubmit = (e) => {
        e.preventDefault();
        
        const sapoCollection = sapoCollections.find(c => c.id == formData.sapo_collection_id);
        const wcCategory = wcCategories.find(c => c.id == formData.wc_category_id);
        
        if (!sapoCollection || !wcCategory) {
            alert('Vui lòng chọn đầy đủ thông tin');
            return;
        }
        
        onSave({
            sapo_collection_id: sapoCollection.id,
            sapo_collection_name: sapoCollection.name,
            wc_category_id: wcCategory.id,
            wc_category_name: wcCategory.name,
            auto_create: formData.auto_create
        });
        
        setFormData({
            sapo_collection_id: '',
            wc_category_id: '',
            auto_create: false
        });
        setShowAddForm(false);
    };
    
    const handleDelete = (collectionId) => {
        if (confirm('Xóa mapping này?')) {
            onDelete(collectionId);
        }
    };
    
    return (
        <div>
            <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px'}}>
                <h2 style={{margin: 0}}>Mapping Categories</h2>
                <div style={{display: 'flex', gap: '10px'}}>
                    <button className="sapo-btn sapo-btn-secondary" onClick={onRefresh}>
                        🔄 Làm mới
                    </button>
                    <button 
                        className="sapo-btn sapo-btn-primary"
                        onClick={() => setShowAddForm(!showAddForm)}
                    >
                        {showAddForm ? '✗ Hủy' : '+ Thêm Mapping'}
                    </button>
                </div>
            </div>
            
            <div className="sapo-card" style={{marginBottom: '20px', padding: '15px', backgroundColor: '#dbeafe'}}>
                <h4 style={{margin: '0 0 10px 0', color: '#1e40af'}}>📘 Hướng dẫn</h4>
                <ul style={{margin: 0, paddingLeft: '20px', fontSize: '14px', color: '#1e3a8a'}}>
                    <li>SAPO dùng <strong>Collections</strong> để nhóm sản phẩm</li>
                    <li>WooCommerce dùng <strong>Product Categories</strong></li>
                    <li>Map SAPO Collection → WC Category để tự động phân loại</li>
                    <li><strong>Auto Create:</strong> Tự động tạo category mới nếu chưa mapping</li>
                </ul>
            </div>
            
            {showAddForm && (
                <div className="sapo-card" style={{marginBottom: '20px'}}>
                    <h3>Thêm Mapping Mới</h3>
                    <form onSubmit={handleSubmit}>
                        <div className="sapo-grid sapo-grid-2">
                            <div>
                                <label className="sapo-label">SAPO Collection *</label>
                                <select 
                                    className="sapo-input"
                                    value={formData.sapo_collection_id}
                                    onChange={(e) => setFormData({...formData, sapo_collection_id: e.target.value})}
                                    required
                                >
                                    <option value="">-- Chọn Collection --</option>
                                    {sapoCollections.map(col => (
                                        <option key={col.id} value={col.id}>
                                            {col.name} (ID: {col.id})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            
                            <div>
                                <label className="sapo-label">WC Category *</label>
                                <select 
                                    className="sapo-input"
                                    value={formData.wc_category_id}
                                    onChange={(e) => setFormData({...formData, wc_category_id: e.target.value})}
                                    required
                                >
                                    <option value="">-- Chọn Category --</option>
                                    {wcCategories.map(cat => (
                                        <option key={cat.id} value={cat.id}>
                                            {cat.name} ({cat.count} products)
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        
                        <div style={{marginTop: '15px'}}>
                            <label style={{display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer'}}>
                                <input 
                                    type="checkbox"
                                    checked={formData.auto_create}
                                    onChange={(e) => setFormData({...formData, auto_create: e.target.checked})}
                                />
                                <span>🤖 Auto-create category nếu chưa mapping</span>
                            </label>
                        </div>
                        
                        <div style={{marginTop: '20px'}}>
                            <button type="submit" className="sapo-btn sapo-btn-primary">
                                💾 Lưu Mapping
                            </button>
                        </div>
                    </form>
                </div>
            )}
            
            {mappings.length === 0 ? (
                <div style={{textAlign: 'center', padding: '40px', color: '#6b7280'}}>
                    <div style={{fontSize: '48px', marginBottom: '15px'}}>🏷️</div>
                    <p>Chưa có mapping nào. Click "Thêm Mapping" để bắt đầu.</p>
                </div>
            ) : (
                <table className="sapo-table">
                    <thead>
                        <tr>
                            <th>SAPO Collection</th>
                            <th>WC Category</th>
                            <th>Auto Create</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        {mappings.map(mapping => (
                            <tr key={mapping.id}>
                                <td>
                                    <strong>{mapping.sapo_collection_name}</strong>
                                    <div style={{fontSize: '12px', color: '#6b7280'}}>
                                        ID: {mapping.sapo_collection_id}
                                    </div>
                                </td>
                                <td>
                                    <strong>{mapping.wc_category_name}</strong>
                                    <div style={{fontSize: '12px', color: '#6b7280'}}>
                                        ID: {mapping.wc_category_id}
                                    </div>
                                </td>
                                <td>
                                    {mapping.auto_create ? (
                                        <span className="sapo-badge sapo-badge-success">✓ Enabled</span>
                                    ) : (
                                        <span className="sapo-badge sapo-badge-error">✗ Disabled</span>
                                    )}
                                </td>
                                <td style={{fontSize: '13px', color: '#6b7280'}}>
                                    {mapping.created_at}
                                </td>
                                <td>
                                    <button 
                                        className="sapo-btn sapo-btn-danger" 
                                        style={{padding: '5px 12px', fontSize: '13px'}}
                                        onClick={() => handleDelete(mapping.sapo_collection_id)}
                                    >
                                        🗑 Xóa
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}

function SapoCategories() {
    const [loading, setLoading] = useState(false);
    const [mappings, setMappings] = useState([]);
    const [sapoCollections, setSapoCollections] = useState([]);
    const [wcCategories, setWcCategories] = useState([]);
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
        loadData();
    }, []);
    
    const loadData = async () => {
        setLoading(true);
        try {
            const [mappingsData, collectionsData, categoriesData] = await Promise.all([
                api('sapo/v1/categories/mappings'),
                api('sapo/v1/categories/sapo-collections'),
                api('sapo/v1/categories/wc-categories')
            ]);
            
            setMappings(mappingsData.mappings || []);
            setSapoCollections(collectionsData.collections || []);
            setWcCategories(categoriesData.categories || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleSave = async (data) => {
        setLoading(true);
        try {
            await api('sapo/v1/categories/mappings', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            showToast('Đã lưu mapping', 'success');
            loadData();
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleDelete = async (collectionId) => {
        setLoading(true);
        try {
            await api(`sapo/v1/categories/mappings/${collectionId}`, { method: 'DELETE' });
            showToast('Đã xóa mapping', 'success');
            loadData();
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    if (loading && mappings.length === 0) {
        return <div className="sapo-loading"><div className="sapo-spinner"></div></div>;
    }
    
    return (
        <div className="sapo-admin-app">
            <CategoryMappingView 
                mappings={mappings}
                sapoCollections={sapoCollections}
                wcCategories={wcCategories}
                onSave={handleSave}
                onDelete={handleDelete}
                onRefresh={loadData}
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

ReactDOM.render(<SapoCategories />, document.getElementById('sapo-categories-root'));
</script>
