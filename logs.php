<?php
// Start session and include database at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Handle API request for logs data
if (isset($_GET['api']) && $_GET['api'] === 'logs') {
    header('Content-Type: application/json');
    
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $actionFilter = isset($_GET['action_filter']) ? $_GET['action_filter'] : '';
    
    $whereClause = "1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (l.description LIKE ? OR l.action LIKE ? OR u.username LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_fill(0, 3, $searchTerm);
    }
    
    if (!empty($actionFilter) && $actionFilter !== 'All Actions') {
        $whereClause .= " AND l.action LIKE ?";
        $params[] = "%$actionFilter%";
    }
    
    $query = "
        SELECT l.*, u.username 
        FROM system_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE $whereClause
        ORDER BY l.created_at DESC 
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Format dates for React
    foreach ($logs as &$log) {
        $log['formatted_date'] = date('Y-m-d H:i:s', strtotime($log['created_at']));
    }
    
    echo json_encode($logs);
    exit();
}

// Handle export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d') . '.csv"');
    
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $actionFilter = isset($_GET['action_filter']) ? $_GET['action_filter'] : '';
    
    $whereClause = "1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (l.description LIKE ? OR l.action LIKE ? OR u.username LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_fill(0, 3, $searchTerm);
    }
    
    if (!empty($actionFilter) && $actionFilter !== 'All Actions') {
        $whereClause .= " AND l.action LIKE ?";
        $params[] = "%$actionFilter%";
    }
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Timestamp', 'User', 'Action', 'Description', 'IP Address']);
    
    $exportQuery = "
        SELECT l.*, u.username 
        FROM system_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE $whereClause
        ORDER BY l.created_at DESC
    ";
    
    $exportStmt = $pdo->prepare($exportQuery);
    $exportStmt->execute($params);
    $logs = $exportStmt->fetchAll();
    
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            date('Y-m-d H:i:s', strtotime($log['created_at'])),
            $log['username'] ?? 'System',
            $log['action'],
            $log['description'],
            $log['ip_address']
        ]);
    }
    
    fclose($output);
    exit();
}

// Now include the header
require_once 'includes/header.php';
?>

<!-- React CDN -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script crossorigin src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<!-- Axios for API calls -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<style>
    /* Custom styles */
    .action-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .bg-blue-100 { background-color: #dbeafe; }
    .text-blue-800 { color: #1e40af; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .text-gray-800 { color: #1f2937; }
    .bg-green-100 { background-color: #d1fae5; }
    .text-green-800 { color: #065f46; }
    .bg-yellow-100 { background-color: #fef3c7; }
    .text-yellow-800 { color: #92400e; }
    .bg-purple-100 { background-color: #e9d5ff; }
    .text-purple-800 { color: #5b21b6; }
    .bg-indigo-100 { background-color: #e0e7ff; }
    .text-indigo-800 { color: #3730a3; }
    .bg-pink-100 { background-color: #fce7f3; }
    .text-pink-800 { color: #9d174d; }
    .bg-red-100 { background-color: #fee2e2; }
    .text-red-800 { color: #991b1b; }
    .bg-teal-100 { background-color: #ccfbf1; }
    .text-teal-800 { color: #115e59; }
    
    /* Custom scrollbar */
    .scrollable-table-container {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    
    .scrollable-table-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    .scrollable-table-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .scrollable-table-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    .scrollable-table-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    /* Responsive fixes */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .table-responsive th,
        .table-responsive td {
            padding: 0.75rem;
        }
        
        .action-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
        }
    }
    
    @media (max-width: 640px) {
        .table-responsive {
            font-size: 0.75rem;
        }
        
        .table-responsive th,
        .table-responsive td {
            padding: 0.5rem;
        }
    }
    
    /* Ensure the logs content has proper spacing */
    .logs-container {
        margin-top: 1rem;
    }
</style>

<div class="fade-in">
    <div class="mx-3 my-4 px-4">
        <!-- React Root -->
        <div id="root"></div>
    </div>
</div>

<!-- React Component -->
<script type="text/babel">
    const { useState, useEffect, useCallback } = React;

    function LogsApp() {
        const [logs, setLogs] = useState([]);
        const [loading, setLoading] = useState(true);
        const [search, setSearch] = useState('');
        const [actionFilter, setActionFilter] = useState('');

        const getActionColor = (action) => {
            const colors = {
                'Login': 'bg-blue-100 text-blue-800',
                'Logout': 'bg-gray-100 text-gray-800',
                'Added transaction': 'bg-green-100 text-green-800',
                'Updated inventory': 'bg-yellow-100 text-yellow-800',
                'Added barber': 'bg-purple-100 text-purple-800',
                'Added service': 'bg-indigo-100 text-indigo-800',
                'Updated service': 'bg-pink-100 text-pink-800',
                'Deleted service': 'bg-red-100 text-red-800',
                'Viewed dashboard': 'bg-indigo-100 text-indigo-800',
                'User registration': 'bg-teal-100 text-teal-800',
                'default': 'bg-gray-100 text-gray-800'
            };
            
            for (const [key, color] of Object.entries(colors)) {
                if (action.toLowerCase().includes(key.toLowerCase())) {
                    return color;
                }
            }
            
            return colors['default'];
        };

        const fetchLogs = useCallback(async () => {
            setLoading(true);
            try {
                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (actionFilter && actionFilter !== 'All Actions') params.append('action_filter', actionFilter);
                params.append('api', 'logs');
                
                const response = await axios.get(`logs.php?${params.toString()}`);
                setLogs(response.data);
            } catch (error) {
                console.error('Error fetching logs:', error);
            } finally {
                setLoading(false);
            }
        }, [search, actionFilter]);

        useEffect(() => {
            fetchLogs();
        }, [fetchLogs]);

        const handleSearchSubmit = (e) => {
            e.preventDefault();
            fetchLogs();
        };

        const handleResetSearch = () => {
            setSearch('');
            setActionFilter('');
        };

        const handleExport = () => {
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (actionFilter) params.append('action_filter', actionFilter);
            params.append('export', '1');
            
            window.location.href = `logs.php?${params.toString()}`;
        };

        return (
            <div className="logs-container">
                <h2 className="text-2xl font-bold mb-6 text-gray-800">System Logs</h2>
                
                <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div className="p-4 md:p-6 border-b border-gray-200 bg-gray-50">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 md:gap-6">
                            <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <input 
                                    type="text" 
                                    placeholder="Search logs..." 
                                    className="px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full sm:w-64 text-sm"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                                <select 
                                    className="px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                    value={actionFilter}
                                    onChange={(e) => setActionFilter(e.target.value)}
                                >
                                    <option value="">All Actions</option>
                                    <option value="Login">Login</option>
                                    <option value="Transaction">Transaction</option>
                                    <option value="Inventory">Inventory</option>
                                    <option value="Barber">Barber</option>
                                    <option value="Service">Service</option>
                                </select>
                                <div className="flex gap-2">
                                    <button 
                                        type="submit" 
                                        className="bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-all duration-200 text-sm font-medium flex items-center gap-2"
                                    >
                                        <i className="fas fa-search"></i>
                                        <span>Search</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        onClick={handleResetSearch}
                                        className="bg-gray-200 text-gray-800 px-4 py-2.5 rounded-lg hover:bg-gray-300 transition-all duration-200 text-sm font-medium flex items-center gap-2"
                                    >
                                        <i className="fas fa-redo"></i>
                                        <span>Reset</span>
                                    </button>
                                </div>
                            </form>
                            <button 
                                onClick={handleExport}
                                className="bg-green-100 text-green-800 hover:bg-green-200 px-4 py-2.5 rounded-lg transition-all duration-200 text-sm font-medium flex items-center gap-2 w-full sm:w-auto"
                            >
                                <i className="fas fa-download"></i>
                                <span>Export Logs</span>
                            </button>
                        </div>
                    </div>
                    
                    {/* Scrollable Table Container */}
                    <div className="overflow-hidden">
                        <div className="scrollable-table-container overflow-auto max-h-[500px]">
                            <table className="w-full min-w-full table-responsive">
                                <thead className="bg-gray-50 sticky top-0 z-10 border-b border-gray-200">
                                    <tr>
                                        <th className="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">ID</th>
                                        <th className="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Timestamp</th>
                                        <th className="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">User</th>
                                        <th className="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Action</th>
                                        <th className="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Description</th>
                                        <th className="p-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {loading ? (
                                        <tr>
                                            <td colSpan="6" className="p-12 text-center">
                                                <div className="flex flex-col items-center justify-center">
                                                    <div className="animate-spin rounded-full h-10 w-10 border-3 border-blue-600 border-t-transparent mb-4"></div>
                                                    <p className="text-gray-600 font-medium">Loading logs...</p>
                                                    <p className="text-sm text-gray-400 mt-1">Please wait</p>
                                                </div>
                                            </td>
                                        </tr>
                                    ) : logs.length > 0 ? (
                                        logs.map(log => (
                                            <tr key={log.id} className="border-b hover:bg-gray-50 transition-colors duration-150 even:bg-gray-50/50">
                                                <td className="p-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                    <span className="font-mono bg-gray-100 px-2 py-1 rounded text-xs">#{log.id}</span>
                                                </td>
                                                <td className="p-4 text-sm text-gray-700 whitespace-nowrap">
                                                    <div className="flex flex-col">
                                                        <span className="font-medium text-gray-800">{log.formatted_date.split(' ')[0]}</span>
                                                        <span className="text-gray-500 text-xs">{log.formatted_date.split(' ')[1]}</span>
                                                    </div>
                                                </td>
                                                <td className="p-4 text-sm text-gray-700 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                            <i className="fas fa-user text-blue-600 text-xs"></i>
                                                        </div>
                                                        <span className="font-medium">{log.username || 'System'}</span>
                                                    </div>
                                                </td>
                                                <td className="p-4 text-sm whitespace-nowrap">
                                                    <span className={`action-badge ${getActionColor(log.action)}`}>
                                                        {log.action}
                                                    </span>
                                                </td>
                                                <td className="p-4 text-sm text-gray-700">
                                                    <span className="truncate max-w-[200px] lg:max-w-[300px] inline-block" title={log.description}>
                                                        {log.description}
                                                    </span>
                                                </td>
                                                <td className="p-4 text-sm text-gray-700 whitespace-nowrap font-mono">
                                                    <span className="bg-gray-100 px-2 py-1 rounded text-xs">{log.ip_address}</span>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="p-12 text-center">
                                                <div className="flex flex-col items-center justify-center">
                                                    <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                        <i className="fas fa-clipboard-list text-gray-400 text-2xl"></i>
                                                    </div>
                                                    <p className="text-gray-600 font-medium mb-2">No logs found</p>
                                                    <p className="text-sm text-gray-400">Try adjusting your search criteria</p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {/* Footer with count */}
                    <div className="px-4 md:px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div className="text-sm text-gray-600">
                                Showing <span className="font-semibold">{logs.length}</span> log entries
                            </div>
                            <div className="text-sm text-gray-500">
                                <i className="fas fa-info-circle mr-2"></i>
                                Latest 50 records displayed
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // Render the React app
    ReactDOM.render(<LogsApp />, document.getElementById('root'));
</script>

<?php require_once 'includes/footer.php'; ?>