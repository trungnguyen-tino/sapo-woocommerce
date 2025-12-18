<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Attribute Mapping</h1>
    <p>Mapping SAPO Options (option1, option2, option3) sang WooCommerce Product Attributes</p>
    
    <div id="sapo-attributes-root"></div>
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

function AttributeMappingView({ mappings, onSave, onDelete }) {
    const [editMode, setEditMode] = useState({});
    const [formData, setFormData] = useState({
        option1: mappings.find(m => m.sapo_option === 'option1') || {},
        option2: mappings.find(m => m.sapo_option === 'option2') || {},
        option3: mappings.find(m => m.sapo_option === 'option3') || {}
    });
    
    const handleSave = (optionKey) => {
        const data = formData[optionKey];
        
        if (!data.wc_attribute_name || !data.wc_attribute_slug) {
            alert('Vui lòng nhập đầy đủ thông tin');
            return;
        }
        
        onSave(optionKey, data);
        setEditMode({...editMode, [optionKey]: false});
    };
    
    const handleDelete = (optionKey) => {
        if (confirm('Xóa mapping này?')) {
            onDelete(optionKey);
            setFormData({
                ...formData,
                [optionKey]: {}
            });
        }
    };
    
    const renderMappingRow = (optionKey, label) => {
        const mapping = formData[optionKey];
        const isEditing = editMode[optionKey];
        
        return (
            <tr key={optionKey}>
                <td style={{fontWeight: '600'}}>
                    <code>{optionKey}</code>
                    <div style={{fontSize: '12px', color: '#6b7280', marginTop: '3px'}}>
                        {label}
                    </div>
                </td>
                <td>
                    {isEditing ? (
                        <input 
                            type="text"
                            className="sapo-input"
                            placeholder="Màu sắc"
                            value={mapping.wc_attribute_name || ''}
                            onChange={(e) => setFormData({
                                ...formData,
                                [optionKey]: {...mapping, wc_attribute_name: e.target.value}
                            })}
                        />
                    ) : (
                        <span>{mapping.wc_attribute_name || '-'}</span>
                    )}
                </td>
                <td>
                    {isEditing ? (
                        <input 
                            type="text"
                            className="sapo-input"
                            placeholder="mau-sac"
                            value={mapping.wc_attribute_slug || ''}
                            onChange={(e) => setFormData({
                                ...formData,
                                [optionKey]: {...mapping, wc_attribute_slug: e.target.value}
                            })}
                        />
                    ) : (
                        <code>{mapping.wc_attribute_slug ? 'pa_' + mapping.wc_attribute_slug : '-'}</code>
                    )}
                </td>
                <td>
                    {isEditing ? (
                        <div style={{display: 'flex', gap: '5px'}}>
                            <button 
                                className="sapo-btn sapo-btn-primary" 
                                style={{padding: '5px 12px', fontSize: '13px'}}
                                onClick={() => handleSave(optionKey)}
                            >
                                ✓ Lưu
                            </button>
                            <button 
                                className="sapo-btn sapo-btn-secondary" 
                                style={{padding: '5px 12px', fontSize: '13px'}}
                                onClick={() => {
                                    setEditMode({...editMode, [optionKey]: false});
                                    setFormData({
                                        ...formData,
                                        [optionKey]: mappings.find(m => m.sapo_option === optionKey) || {}
                                    });
                                }}
                            >
                                ✗ Hủy
                            </button>
                        </div>
                    ) : (
                        <div style={{display: 'flex', gap: '5px'}}>
                            <button 
                                className="sapo-btn sapo-btn-primary" 
                                style={{padding: '5px 12px', fontSize: '13px'}}
                                onClick={() => setEditMode({...editMode, [optionKey]: true})}
                            >
                                {mapping.wc_attribute_name ? '✎ Sửa' : '+ Thêm'}
                            </button>
                            {mapping.wc_attribute_name && (
                                <button 
                                    className="sapo-btn sapo-btn-danger" 
                                    style={{padding: '5px 12px', fontSize: '13px'}}
                                    onClick={() => handleDelete(optionKey)}
                                >
                                    🗑
                                </button>
                            )}
                        </div>
                    )}
                </td>
            </tr>
        );
    };
    
    return (
        <div>
            <h2>Mapping Product Attributes</h2>
            
            <div className="sapo-card" style={{marginBottom: '20px', padding: '15px', backgroundColor: '#dbeafe'}}>
                <h4 style={{margin: '0 0 10px 0', color: '#1e40af'}}>📘 Hướng dẫn</h4>
                <ul style={{margin: 0, paddingLeft: '20px', fontSize: '14px', color: '#1e3a8a'}}>
                    <li>SAPO chỉ trả về <code>option1</code>, <code>option2</code>, <code>option3</code> cố định</li>
                    <li>Bạn cần map các option này tới WooCommerce Product Attributes</li>
                    <li><strong>Attribute Name:</strong> Tên hiển thị (vd: Màu sắc, Kích thước)</li>
                    <li><strong>Attribute Slug:</strong> Slug URL-friendly (vd: mau-sac, kich-thuoc)</li>
                </ul>
            </div>
            
            <table className="sapo-table">
                <thead>
                    <tr>
                        <th style={{width: '150px'}}>SAPO Option</th>
                        <th>WC Attribute Name</th>
                        <th>WC Attribute Slug</th>
                        <th style={{width: '200px'}}>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    {renderMappingRow('option1', 'Thuộc tính 1')}
                    {renderMappingRow('option2', 'Thuộc tính 2')}
                    {renderMappingRow('option3', 'Thuộc tính 3')}
                </tbody>
            </table>
        </div>
    );
}

function SapoAttributes() {
    const [loading, setLoading] = useState(false);
    const [mappings, setMappings] = useState([]);
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
        loadMappings();
    }, []);
    
    const loadMappings = async () => {
        setLoading(true);
        try {
            const data = await api('sapo/v1/attributes/mappings');
            setMappings(data.mappings || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleSave = async (sapoOption, data) => {
        setLoading(true);
        try {
            await api('sapo/v1/attributes/mappings', {
                method: 'POST',
                body: JSON.stringify({
                    sapo_option: sapoOption,
                    wc_attribute_name: data.wc_attribute_name,
                    wc_attribute_slug: data.wc_attribute_slug
                })
            });
            showToast('Đã lưu mapping', 'success');
            loadMappings();
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleDelete = async (sapoOption) => {
        setLoading(true);
        try {
            await api(`sapo/v1/attributes/mappings/${sapoOption}`, { method: 'DELETE' });
            showToast('Đã xóa mapping', 'success');
            loadMappings();
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
            <AttributeMappingView 
                mappings={mappings}
                onSave={handleSave}
                onDelete={handleDelete}
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

ReactDOM.render(<SapoAttributes />, document.getElementById('sapo-attributes-root'));
</script>
