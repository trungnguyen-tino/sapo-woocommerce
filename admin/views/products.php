<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Sản phẩm SAPO</h1>
    <p>Danh sách sản phẩm từ SAPO, chọn để đồng bộ sang WooCommerce</p>
    
    <div id="sapo-products-root"></div>
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

function ProductsView({ products, onSync, loading }) {
    const [selectedProducts, setSelectedProducts] = useState([]);
    
    const toggleProduct = (productId) => {
        if (selectedProducts.includes(productId)) {
            setSelectedProducts(selectedProducts.filter(id => id !== productId));
        } else {
            setSelectedProducts([...selectedProducts, productId]);
        }
    };
    
    const handleBulkSync = () => {
        if (selectedProducts.length === 0) return;
        onSync(selectedProducts);
        setSelectedProducts([]);
    };
    
    if (products.length === 0) {
        return (
            <div style={{textAlign: 'center', padding: '40px', color: '#6b7280'}}>
                <div style={{fontSize: '48px', marginBottom: '15px'}}>📦</div>
                <p>Không có sản phẩm nào từ SAPO</p>
            </div>
        );
    }
    
    return (
        <div>
            <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px'}}>
                <h2 style={{margin: 0}}>Sản phẩm từ SAPO ({products.length})</h2>
                {selectedProducts.length > 0 && (
                    <button 
                        className="sapo-btn sapo-btn-primary"
                        onClick={handleBulkSync}
                        disabled={loading}
                    >
                        Đồng bộ {selectedProducts.length} sản phẩm
                    </button>
                )}
            </div>
            
            <table className="sapo-table">
                <thead>
                    <tr>
                        <th style={{width: '40px'}}>
                            <input 
                                type="checkbox"
                                onChange={(e) => {
                                    if (e.target.checked) {
                                        setSelectedProducts(products.map(p => p.id));
                                    } else {
                                        setSelectedProducts([]);
                                    }
                                }}
                                checked={selectedProducts.length === products.length}
                            />
                        </th>
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
                            <td>
                                <input 
                                    type="checkbox"
                                    checked={selectedProducts.includes(product.id)}
                                    onChange={() => toggleProduct(product.id)}
                                />
                            </td>
                            <td>{product.id}</td>
                            <td>
                                <strong>{product.name}</strong>
                                {product.variants && product.variants.length > 1 && (
                                    <div style={{fontSize: '12px', color: '#6b7280'}}>
                                        {product.variants.length} biến thể
                                    </div>
                                )}
                            </td>
                            <td>{product.sku || '-'}</td>
                            <td>{product.price ? product.price.toLocaleString() + '₫' : '-'}</td>
                            <td>{product.inventory_quantity || 0}</td>
                            <td>
                                <button 
                                    className="sapo-btn sapo-btn-primary" 
                                    style={{padding: '5px 10px', fontSize: '12px'}}
                                    onClick={() => onSync([product.id])}
                                    disabled={loading}
                                >
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

function SapoProducts() {
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
            const data = await api('sapo/v1/products?limit=50&page=1');
            setProducts(data.products || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleSync = async (productIds) => {
        setLoading(true);
        try {
            await api('sapo/v1/products/sync', {
                method: 'POST',
                body: JSON.stringify({ product_ids: productIds })
            });
            showToast(`Đồng bộ ${productIds.length} sản phẩm thành công`, 'success');
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
            <ProductsView 
                products={products}
                onSync={handleSync}
                loading={loading}
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

ReactDOM.render(<SapoProducts />, document.getElementById('sapo-products-root'));
</script>
