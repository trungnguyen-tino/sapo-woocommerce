<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Nhật ký đồng bộ</h1>
    <p>Lịch sử các hoạt động đồng bộ và lỗi</p>
    
    <div id="sapo-logs-root"></div>
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

function LogsView({ logs, onClearLogs, onRefresh }) {
    const [filter, setFilter] = useState({ log_type: '', status: '' });
    
    const filteredLogs = logs.filter(log => {
        if (filter.log_type && log.log_type !== filter.log_type) return false;
        if (filter.status && log.status !== filter.status) return false;
        return true;
    });
    
    if (logs.length === 0) {
        return (
            <div style={{textAlign: 'center', padding: '40px', color: '#6b7280'}}>
                <div style={{fontSize: '48px', marginBottom: '15px'}}>📝</div>
                <p>Chưa có nhật ký nào</p>
            </div>
        );
    }
    
    return (
        <div>
            <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px'}}>
                <h2 style={{margin: 0}}>Nhật ký đồng bộ ({filteredLogs.length})</h2>
                <div style={{display: 'flex', gap: '10px'}}>
                    <button className="sapo-btn sapo-btn-secondary" onClick={onRefresh}>
                        🔄 Làm mới
                    </button>
                    <button 
                        className="sapo-btn sapo-btn-danger" 
                        onClick={() => {
                            if (confirm('Xóa tất cả logs?')) {
                                onClearLogs();
                            }
                        }}
                    >
                        🗑️ Xóa logs
                    </button>
                </div>
            </div>
            
            <div style={{marginBottom: '20px', display: 'flex', gap: '10px'}}>
                <select 
                    className="sapo-input" 
                    style={{width: '200px'}}
                    value={filter.log_type}
                    onChange={(e) => setFilter({...filter, log_type: e.target.value})}
                >
                    <option value="">Tất cả loại</option>
                    <option value="product">Product</option>
                    <option value="sync">Sync</option>
                    <option value="auth">Auth</option>
                    <option value="webhook">Webhook</option>
                </select>
                
                <select 
                    className="sapo-input" 
                    style={{width: '200px'}}
                    value={filter.status}
                    onChange={(e) => setFilter({...filter, status: e.target.value})}
                >
                    <option value="">Tất cả trạng thái</option>
                    <option value="success">Success</option>
                    <option value="error">Error</option>
                    <option value="info">Info</option>
                </select>
            </div>
            
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
                    {filteredLogs.map(log => (
                        <tr key={log.id}>
                            <td style={{fontSize: '13px', color: '#6b7280'}}>
                                {log.created_at}
                            </td>
                            <td>
                                <span className="sapo-badge sapo-badge-info">
                                    {log.log_type}
                                </span>
                            </td>
                            <td>{log.action}</td>
                            <td>
                                <span className={`sapo-badge sapo-badge-${log.status === 'success' ? 'success' : log.status === 'error' ? 'error' : 'info'}`}>
                                    {log.status}
                                </span>
                            </td>
                            <td style={{fontSize: '13px', maxWidth: '400px', wordBreak: 'break-word'}}>
                                {log.message}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function SapoLogs() {
    const [loading, setLoading] = useState(false);
    const [logs, setLogs] = useState([]);
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
        loadLogs();
    }, []);
    
    const loadLogs = async () => {
        setLoading(true);
        try {
            const data = await api('sapo/v1/logs?limit=100&offset=0');
            setLogs(data.logs || []);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    const handleClearLogs = async () => {
        setLoading(true);
        try {
            await api('sapo/v1/logs/clear', { method: 'POST' });
            showToast('Đã xóa logs', 'success');
            setLogs([]);
        } catch (error) {
            showToast(error.message, 'error');
        }
        setLoading(false);
    };
    
    if (loading && logs.length === 0) {
        return <div className="sapo-loading"><div className="sapo-spinner"></div></div>;
    }
    
    return (
        <div className="sapo-admin-app">
            <LogsView 
                logs={logs}
                onClearLogs={handleClearLogs}
                onRefresh={loadLogs}
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

ReactDOM.render(<SapoLogs />, document.getElementById('sapo-logs-root'));
</script>
