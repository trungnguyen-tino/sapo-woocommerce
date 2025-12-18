<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Test & Debug</h1>
    <p>Test đồng bộ và xem API mapping chi tiết</p>
    
    <div id="sapo-debug-root"></div>
</div>

<script type="text/babel">
const { useState } = React;

function Toast({ message, type, onClose }) {
    React.useEffect(() => {
        const timer = setTimeout(onClose, 3000);
        return () => clearTimeout(timer);
    }, []);
    
    return (
        <div className={`sapo-toast ${type === 'success' ? 'sapo-toast-success' : 'sapo-toast-error'}`}>
            <strong>{type === 'success' ? '✓' : '✗'}</strong> {message}
        </div>
    );
}

function SapoDebug() {
    const [productId, setProductId] = useState('');
    const [loading, setLoading] = useState(false);
    const [activeTab, setActiveTab] = useState('product');
    const [results, setResults] = useState(null);
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
    
    const handleTestProduct = async () => {
        if (!productId) {
            showToast('Vui lòng nhập Product ID', 'error');
            return;
        }
        
        setLoading(true);
        try {
            const data = await api(`sapo/v1/debug/test-product/${productId}`, { method: 'POST' });
            setResults(data);
            setActiveTab('product');
            showToast('Test thành công!', 'success');
        } catch (error) {
            showToast(error.message, 'error');
            setResults(null);
        }
        setLoading(false);
    };
    
    const handleTestVariants = async () => {
        if (!productId) {
            showToast('Vui lòng nhập Product ID', 'error');
            return;
        }
        
        setLoading(true);
        try {
            const data = await api(`sapo/v1/debug/test-variant/${productId}`, { method: 'POST' });
            setResults(data);
            setActiveTab('variants');
            showToast('Test variants thành công!', 'success');
        } catch (error) {
            showToast(error.message, 'error');
            setResults(null);
        }
        setLoading(false);
    };
    
    const handleTestImages = async () => {
        if (!productId) {
            showToast('Vui lòng nhập Product ID', 'error');
            return;
        }
        
        setLoading(true);
        try {
            const data = await api(`sapo/v1/debug/test-images/${productId}`, { method: 'POST' });
            setResults(data);
            setActiveTab('images');
            showToast('Test images thành công!', 'success');
        } catch (error) {
            showToast(error.message, 'error');
            setResults(null);
        }
        setLoading(false);
    };
    
    const handleGetRawAPI = async () => {
        if (!productId) {
            showToast('Vui lòng nhập Product ID', 'error');
            return;
        }
        
        setLoading(true);
        try {
            const data = await api(`sapo/v1/debug/api-raw/${productId}`);
            setResults(data);
            setActiveTab('raw');
            showToast('Lấy raw data thành công!', 'success');
        } catch (error) {
            showToast(error.message, 'error');
            setResults(null);
        }
        setLoading(false);
    };
    
    return (
        <div className="sapo-admin-app">
            <div className="sapo-card" style={{marginBottom: '20px'}}>
                <h3>Test Sync Product</h3>
                <p>Nhập SAPO Product ID để test đồng bộ và xem mapping chi tiết</p>
                
                <div style={{display: 'flex', gap: '10px', marginTop: '15px', alignItems: 'flex-end'}}>
                    <div style={{flex: 1}}>
                        <label className="sapo-label">SAPO Product ID *</label>
                        <input 
                            type="number"
                            className="sapo-input"
                            placeholder="632910392"
                            value={productId}
                            onChange={(e) => setProductId(e.target.value)}
                        />
                    </div>
                    <button 
                        className="sapo-btn sapo-btn-primary"
                        onClick={handleTestProduct}
                        disabled={loading}
                    >
                        🧪 Test Product
                    </button>
                    <button 
                        className="sapo-btn sapo-btn-secondary"
                        onClick={handleTestVariants}
                        disabled={loading}
                    >
                        🔄 Test Variants
                    </button>
                    <button 
                        className="sapo-btn sapo-btn-secondary"
                        onClick={handleTestImages}
                        disabled={loading}
                    >
                        🖼️ Test Images
                    </button>
                    <button 
                        className="sapo-btn sapo-btn-secondary"
                        onClick={handleGetRawAPI}
                        disabled={loading}
                    >
                        📡 Raw API
                    </button>
                </div>
            </div>
            
            {loading && (
                <div className="sapo-loading" style={{padding: '40px'}}>
                    <div className="sapo-spinner"></div>
                    <p>Đang test...</p>
                </div>
            )}
            
            {results && !loading && (
                <>
                    {activeTab === 'product' && (
                        <div>
                            <div className="sapo-card" style={{marginBottom: '20px', backgroundColor: '#d1fae5'}}>
                                <h3 style={{color: '#065f46'}}>✓ Đồng bộ thành công!</h3>
                                <p style={{margin: '5px 0'}}>WC Product ID: <strong>#{results.wc_product_id}</strong></p>
                                <a href={`/wp-admin/post.php?post=${results.wc_product_id}&action=edit`} target="_blank" className="sapo-btn sapo-btn-primary" style={{marginTop: '10px'}}>
                                    Xem sản phẩm WC ↗
                                </a>
                            </div>
                            
                            <div className="sapo-card" style={{marginBottom: '20px'}}>
                                <h3>📊 Field Mapping</h3>
                                <table className="sapo-table">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>SAPO Value</th>
                                            <th>WC Value</th>
                                            <th>Mapped</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(results.field_mapping || {}).map(([field, data]) => (
                                            <tr key={field}>
                                                <td><strong>{field}</strong></td>
                                                <td><code>{JSON.stringify(data.sapo)}</code></td>
                                                <td><code>{JSON.stringify(data.wc)}</code></td>
                                                <td>
                                                    {data.mapped ? (
                                                        <span className="sapo-badge sapo-badge-success">✓</span>
                                                    ) : (
                                                        <span className="sapo-badge sapo-badge-error">✗</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            
                            {results.unmapped_fields && results.unmapped_fields.length > 0 && (
                                <div className="sapo-card" style={{marginBottom: '20px', backgroundColor: '#fef3c7'}}>
                                    <h3 style={{color: '#92400e'}}>⚠️ Unmapped Fields ({results.unmapped_fields.length})</h3>
                                    <p>Các field từ SAPO chưa được map:</p>
                                    <div style={{maxHeight: '300px', overflow: 'auto'}}>
                                        {results.unmapped_fields.map((field, idx) => (
                                            <div key={idx} style={{padding: '8px', borderBottom: '1px solid #e5e7eb'}}>
                                                <strong>{field.field}</strong> ({field.type}): 
                                                <code style={{marginLeft: '10px'}}>{JSON.stringify(field.value).substring(0, 100)}</code>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                            
                            <div className="sapo-card">
                                <h3>🔍 WC Product Data</h3>
                                <pre style={{background: '#f3f4f6', padding: '15px', borderRadius: '4px', overflow: 'auto', maxHeight: '400px'}}>
                                    {JSON.stringify(results.wc_product_data, null, 2)}
                                </pre>
                            </div>
                        </div>
                    )}
                    
                    {activeTab === 'variants' && (
                        <div>
                            <div className="sapo-card" style={{marginBottom: '20px', backgroundColor: '#d1fae5'}}>
                                <h3 style={{color: '#065f46'}}>✓ Test Variants thành công!</h3>
                                <p>Tổng: <strong>{results.total_variants}</strong> variants</p>
                                <p>WC Product ID: <strong>#{results.wc_product_id}</strong></p>
                            </div>
                            
                            <div className="sapo-card" style={{marginBottom: '20px'}}>
                                <h3>🏷️ Attribute Mapping Analysis</h3>
                                <pre style={{background: '#f3f4f6', padding: '15px', borderRadius: '4px', overflow: 'auto'}}>
                                    {JSON.stringify(results.attribute_mapping, null, 2)}
                                </pre>
                            </div>
                            
                            <div className="sapo-card">
                                <h3>📦 Variants Detail</h3>
                                {results.variants && results.variants.map((variant, idx) => (
                                    <details key={idx} style={{marginBottom: '15px', border: '1px solid #e5e7eb', borderRadius: '4px', padding: '10px'}}>
                                        <summary style={{cursor: 'pointer', fontWeight: 'bold'}}>
                                            Variant {idx + 1} - WC ID: #{variant.wc_variation_id}
                                        </summary>
                                        <div style={{marginTop: '10px'}}>
                                            <h4>SAPO Variant:</h4>
                                            <pre style={{background: '#f3f4f6', padding: '10px', borderRadius: '4px', fontSize: '12px'}}>
                                                {JSON.stringify(variant.sapo_variant, null, 2)}
                                            </pre>
                                            <h4>WC Variation:</h4>
                                            <pre style={{background: '#f3f4f6', padding: '10px', borderRadius: '4px', fontSize: '12px'}}>
                                                {JSON.stringify(variant.wc_variation_data, null, 2)}
                                            </pre>
                                        </div>
                                    </details>
                                ))}
                            </div>
                        </div>
                    )}
                    
                    {activeTab === 'images' && (
                        <div>
                            <div className="sapo-card" style={{marginBottom: '20px', backgroundColor: '#d1fae5'}}>
                                <h3 style={{color: '#065f46'}}>✓ Test Images thành công!</h3>
                                <p>Tổng: <strong>{results.total_images}</strong> images</p>
                                <p>Product images: <strong>{Object.keys(results.product_images || {}).length}</strong></p>
                                <p>Variant images: <strong>{Object.keys(results.variant_images || {}).length}</strong></p>
                            </div>
                            
                            <div className="sapo-card">
                                <h3>🖼️ Images Detail</h3>
                                <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))', gap: '15px'}}>
                                    {results.images && results.images.map((image, idx) => (
                                        <div key={idx} style={{border: '1px solid #e5e7eb', borderRadius: '4px', padding: '10px'}}>
                                            <img src={image.src} alt="" style={{width: '100%', height: '200px', objectFit: 'cover', borderRadius: '4px'}} />
                                            <p style={{margin: '10px 0 5px', fontSize: '12px'}}>
                                                <strong>Position:</strong> {image.position}
                                            </p>
                                            <p style={{margin: '5px 0', fontSize: '12px'}}>
                                                <strong>Type:</strong> {image.is_product_image ? '📦 Product' : '🔄 Variant'}
                                            </p>
                                            {!image.is_product_image && (
                                                <p style={{margin: '5px 0', fontSize: '12px'}}>
                                                    <strong>Variant IDs:</strong> {image.variant_ids.join(', ')}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                    
                    {activeTab === 'raw' && (
                        <div>
                            <div className="sapo-card" style={{marginBottom: '20px'}}>
                                <h3>📡 SAPO API Response</h3>
                                <pre style={{background: '#1f2937', color: '#fff', padding: '15px', borderRadius: '4px', overflow: 'auto', maxHeight: '500px'}}>
                                    {JSON.stringify(results.product, null, 2)}
                                </pre>
                            </div>
                            
                            <div className="sapo-card" style={{marginBottom: '20px'}}>
                                <h3>🏷️ Collections (Collects)</h3>
                                <pre style={{background: '#1f2937', color: '#fff', padding: '15px', borderRadius: '4px', overflow: 'auto'}}>
                                    {JSON.stringify(results.collects, null, 2)}
                                </pre>
                            </div>
                            
                            <div className="sapo-card">
                                <h3>📋 API Structure</h3>
                                <table className="sapo-table">
                                    <thead>
                                        <tr>
                                            <th>Field Path</th>
                                            <th>Type & Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(results.api_structure || {}).map(([key, value]) => (
                                            <tr key={key}>
                                                <td><code>{key}</code></td>
                                                <td style={{fontSize: '12px'}}>{value}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
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

ReactDOM.render(<SapoDebug />, document.getElementById('sapo-debug-root'));
</script>
EOFPHP
