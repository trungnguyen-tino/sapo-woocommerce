<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Sản phẩm đã đồng bộ</h1>
    <p>Danh sách sản phẩm đã đồng bộ từ SAPO sang WooCommerce</p>
    
    <div id="sapo-synced-root"></div>
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

function SyncedProductsView({ products, onRefresh, onResync, onDeleteMapping }) {
    if (products.length === 0) {
        return (
            <div style={{textAlign: 'center', padding: '40px', color: '#6b7280'}}>
                <div style={{fontSize: '48px', marginBottom: '15px'}}>📋</div>
                <p>Chưa có sản phẩm đã đồng bộ</p>
            </div>
        );
    }
    
    return (
        <div>
            <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px'}}>
                <h2 style={{margin: 0}}>Sản phẩm đã đồng bộ ({products.length})</h2>
                <button className="sapo-btn sapo-btn-secondary" onClick={onRefresh}>
                    🔄 Làm mới
                </button>
            </div>
            
            <table className="sapo-table">
                <thead>
                    <tr>
                        <th>SAPO ID</th>
                        <th>WC Product</th>
                        <th>Loại</th>
                        <th>Đồng bộ lần cuối</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    {products.map(product => (
                        <tr key={product.id}>
                            <td>
                                <strong>#{product.sapo_product_id}</strong>
                                {product.sapo_variant_id && (
                                    <div style={{fontSize: '12px', color: '#6b7280'}}>
                                        Variant: {product.sapo_variant_id}
                                    </div>
                                )}
                            </td>
                            <td>
                                <a 
                                    href={`/wp-admin/post.php?post=${product.wc_product_id}&action=edit`}
                                    target="_blank"
                                    style={{color: '#2563eb', textDecoration: 'none'}}
                                >
                                    #{product.wc_product_id} ↗
                                </a>
                            </td>
                            <td>
                                <span className="sapo-badge sapo-badge-info">
                                    {product.product_type}
                                </span>
                            </td>
                            <td style={{fontSize: '13px', color: '#6b7280'}}>
                                {product.last_synced}
                            </td>
                            <td>
                                <button 
                                    className="sapo-btn sapo-btn-primary" 
                                    style={{padding: '5px 10px', fontSize: '12px', marginRight: '5px'}}
                                    onClick={() => onResync(product.sapo_product_id)}
                                >
                                    Đồng bộ lại
                                </button>
                                <button 
                                    className="sapo-btn sapo-btn-danger" 
                                    style={{padding: '5px 10px', fontSize: '12px'}}
                                    onClick={() => {
                                        if (confirm('Xóa mapping này? Sản phẩm WC sẽ không bị xóa.')) {
                                            onDeleteMapping(product.id);
                                        }
                                    }}
                                >
                                    Xóa mapping
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function SapoSynced() {
    const [loading, setLoading] = useState(false);
    const [products, setProducts] = useState([]);
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
        loadProducts();
    }, []);
    
    const loadProducts = async () => {
        setLoading(true);
        try {
            const data = await api('sapo/v1/products/synced?limit=50&offset=0');
            setProducts(data.products || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleResync = async (productId) => {
        setLoading(true);
        try {
            await api(`sapo/v1/products/${productId}/re-sync`, { method: 'POST' });
            showToast('Đã đồng bộ lại sản phẩm', 'success');
            loadProducts();
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleDelete = async (mappingId) => {
        setLoading(true);
        try {
            await api(`sapo/v1/products/mapping/${mappingId}`, { method: 'DELETE' });
            showToast('Đã xóa mapping', 'success');
            loadProducts();
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    if (loading && products.length === 0) {
        return <div className="sapo-loading"><div className="sapo-spinner"></div></div>;
    }
    
    return (
        <div className="sapo-admin-app">
            <SyncedProductsView 
                products={products}
                onRefresh={loadProducts}
                onResync={handleResync}
                onDeleteMapping={handleDelete}
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

ReactDOM.render(<SapoSynced />, document.getElementById('sapo-synced-root'));
</script>
