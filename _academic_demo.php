<?php
$page_sql_logs = [];
$demo_status = '';
$demo_message = '';

function addLog(&$logs, $query, $result = "SUCCESS") {
    $time = date('H:i:s');
    $logs[] = "[$time] $query => $result";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demo_action'])) {
    $action = $_POST['demo_action'];
    if ($action === 'tcl_commit' || $action === 'tcl_rollback') {
        try {
            
            addLog($page_sql_logs, "START TRANSACTION");
            $conn->begin_transaction();
            
            
            $rand_stmt = $conn->query("SELECT id, stock FROM motorcycles LIMIT 1");
            $motor = $rand_stmt->fetch_assoc();
            $mid = $motor['id'];
            addLog($page_sql_logs, "SELECT id, stock FROM motorcycles LIMIT 1");
            
            $qty = 1;
            
            
            $update_sql = "UPDATE motorcycles SET stock = stock - $qty WHERE id = $mid";
            addLog($page_sql_logs, $update_sql);
            $conn->query($update_sql);
            
            
            $uid = $_SESSION['user_id'];
            $insert_sql = "INSERT INTO transactions (user_id, motorcycle_id, quantity, type, payment_status, status) VALUES ($uid, $mid, $qty, 'buy', 'unpaid', 'pending')";
            addLog($page_sql_logs, $insert_sql);
            $conn->query($insert_sql);
            
            if ($action === 'tcl_commit') {
                addLog($page_sql_logs, "COMMIT");
                $conn->commit();
                $demo_status = 'success';
                $demo_message = 'TCL Demo (COMMIT) successful! Data was permanently saved to the database.';
            } else {
                addLog($page_sql_logs, "ROLLBACK");
                $conn->rollback();
                $demo_status = 'success';
                $demo_message = 'TCL Demo (ROLLBACK) successful! Changes were undone and database remains unchanged.';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $demo_status = 'error';
            $demo_message = 'TCL Demo Failed (Constraint Triggered / Exception): ' . htmlspecialchars($e->getMessage());
            addLog($page_sql_logs, "ROLLBACK (Due to Error) => " . htmlspecialchars($e->getMessage()), "FAILED");
        }
    }
}
?>

<section id="academic_demo" class="w-full">
    <div class="mb-8 border-b border-outline-variant pb-6">
        <h1 class="text-3xl font-bold mb-2">Academic Requirements Demo</h1>
        <p class="text-on-surface-variant">Interactive demonstration for advanced database concepts (Table Locking & Transaction Control). Actions are isolated to prevent accidental database corruption.</p>
    </div>

    <?php if ($demo_message): ?>
    <div class="p-4 rounded-lg mb-6 flex items-start gap-3 border <?= $demo_status === 'success' ? 'bg-green-500/10 border-green-500 text-green-700 dark:text-green-400' : 'bg-red-500/10 border-red-500 text-red-700 dark:text-red-400' ?>">
        <span class="material-symbols-outlined"><?= $demo_status === 'success' ? 'check_circle' : 'error' ?></span>
        <div>
            <h4 class="font-bold"><?= $demo_status === 'success' ? 'Demo Execution Successful' : 'Demo Execution Failed' ?></h4>
            <p class="text-sm mt-1"><?= $demo_message ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col relative overflow-hidden">
            <h2 class="text-xl font-bold mb-2 text-secondary">
                Table Locking
            </h2>
            <p class="text-on-surface-variant text-sm mb-6 flex-1">
                Prevents race conditions by locking tables explicitly. <br><br>
                <strong>Simulation:</strong> Clicking the button will execute a <code>WRITE</code> lock on the database, pause the script for <strong><?= __('10 seconds', '10 detik') ?></strong>, and then <code>UNLOCK</code> it. <br><br>
                <em><?= __('Tip: Open a new tab to the Motorcycle Catalog immediately after clicking. That tab will hang for 10 seconds!', 'Tip: Segera buka tab baru ke Motorcycle Catalog setelah klik. Tab tersebut akan tertahan (hang) selama 10 detik!') ?></em>
            </p>
            <form id="lockForm" onsubmit="startLockTimer(event)">
                <button type="submit" id="btn-lock" class="w-full flex items-center justify-center px-6 py-3 bg-secondary text-white dark:text-slate-950 rounded-xl font-semibold hover:bg-opacity-90 transition-all shadow-md transform hover:-translate-y-0.5">
                    <span><?= __('Simulate 10-Second Lock', 'Simulasi Lock 10 Detik') ?></span>
                </button>
            </form>
            
            <script>
                function addLogUI(query, status) {
                    const logsContainer = document.getElementById('sql-logs-container');
                    const noLogs = document.getElementById('no-logs-msg');
                    if (noLogs) noLogs.remove();
                    
                    const time = new Date().toLocaleTimeString('en-US', { hour12: false });
                    const div = document.createElement('div');
                    div.className = "mb-2 break-all";
                    div.innerHTML = `> [${time}] ${query} => ${status}`;
                    logsContainer.appendChild(div);
                    logsContainer.scrollTop = logsContainer.scrollHeight;
                }

                function startLockTimer(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btn-lock');
                    const textSpan = btn.querySelector('span');
                    
                    btn.disabled = true;
                    btn.className = "w-full flex items-center justify-center px-6 py-3 bg-slate-700 text-slate-400 rounded-xl font-semibold cursor-not-allowed transition-all text-sm";
                    
                    addLogUI("LOCK TABLES motorcycles WRITE, transactions WRITE, users READ", "SUCCESS (Locked for 10s)");
                    
                    let timeLeft = 10;
                    textSpan.innerText = `<?= __('Locked...', 'Terkunci...') ?> (${timeLeft}s <?= __('left', 'tersisa') ?>)`;
                    
                    const interval = setInterval(() => {
                        timeLeft--;
                        if(timeLeft > 0) {
                            textSpan.innerText = `<?= __('Locked...', 'Terkunci...') ?> (${timeLeft}s <?= __('left', 'tersisa') ?>)`;
                        }
                    }, 1000);
                    
                    fetch('ajax_lock', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=lock_timer'
                    }).then(res => res.json()).then(data => {
                        clearInterval(interval);
                        addLogUI("UNLOCK TABLES", "SUCCESS");
                        resetButton();
                    }).catch(err => {
                        clearInterval(interval);
                        addLogUI("ERROR: Connection Lost", "FAILED");
                        resetButton();
                    });
                }
                
                function resetButton() {
                    const btn = document.getElementById('btn-lock');
                    btn.disabled = false;
                    btn.className = "w-full flex items-center justify-center px-6 py-3 bg-secondary text-white dark:text-slate-950 rounded-xl font-semibold hover:bg-opacity-90 transition-all shadow-md transform hover:-translate-y-0.5";
                    btn.innerHTML = '<span><?= __('Simulate 10-Second Lock', 'Simulasi Lock 10 Detik') ?></span>';
                }
            </script>
        </div>

        
        <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm flex flex-col relative overflow-hidden">
            <h2 class="text-xl font-bold mb-2 text-secondary">
                Transaction Control (TCL)
            </h2>
            <p class="text-on-surface-variant text-sm mb-6 flex-1">
                Ensures Atomicity (ACID properties) by bundling queries together. <br><br>
                <strong>Simulation:</strong> We will execute a <code>START TRANSACTION</code>, deduct motorcycle stock, and insert a dummy transaction. Then you can choose whether to permanently save it (<code>COMMIT</code>) or completely undo it (<code>ROLLBACK</code>).
            </p>
            <div class="flex gap-4">
                <form method="POST" class="flex-1">
                    <input type="hidden" name="demo_action" value="tcl_commit">
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm">
                        COMMIT
                    </button>
                </form>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="demo_action" value="tcl_rollback">
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm">
                        ROLLBACK
                    </button>
                </form>
            </div>
        </div>
    </div>

    
    <style>
        #sql-manual-form input#manual-query-input {
            background-color: transparent !important;
            color: #10b981 !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }
        #sql-manual-form input#manual-query-input::placeholder {
            color: rgba(16, 185, 129, 0.4) !important;
        }
    </style>
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-lg relative overflow-hidden flex flex-col">
        <div class="absolute top-0 left-0 w-full h-8 bg-surface-container flex items-center justify-between px-4 border-b border-outline-variant z-10">
            <div class="flex items-center">
                <div class="flex gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <span class="text-on-surface-variant text-xs font-mono ml-4 select-none">SQL Execution Logs - Current Session</span>
            </div>
            <button type="submit" form="sql-manual-form" class="text-[10px] bg-surface-container-lowest border border-outline-variant text-secondary hover:bg-surface-container px-3 py-0.5 rounded-full font-semibold transition-all select-none hover:scale-105 active:scale-[0.98]">
                Execute
            </button>
        </div>
        <div id="sql-logs-container" class="mt-6 font-mono text-sm text-green-600 dark:text-green-400 min-h-[150px] max-h-[300px] overflow-y-auto flex-1 pt-2">
            <?php if (empty($page_sql_logs)): ?>
                <div id="no-logs-msg" class="text-on-surface-variant italic flex items-center gap-2 opacity-50 mt-4 select-none">
                    <span class="material-symbols-outlined">terminal</span>
                    No queries executed yet. Run a demo above to view logs.
                </div>
            <?php else: ?>
                <?php foreach ($page_sql_logs as $log): ?>
                    <div class="mb-2 break-all">> <?= htmlspecialchars($log) ?></div>
                <?php endforeach; ?>
                <div class="mt-4 text-on-surface-variant opacity-75">--- End of PHP execution ---</div>
            <?php endif; ?>
        </div>
        
        
        <form id="sql-manual-form" onsubmit="executeManualQuery(event)" class="mt-4 border-t border-outline-variant pt-3 flex items-center gap-2">
            <span class="font-mono text-green-600 dark:text-green-400 font-bold select-none">&gt;</span>
            <input type="text" id="manual-query-input" name="query" placeholder="Enter SQL (e.g. SELECT * FROM motorcycles)" class="flex-1 font-mono text-sm focus:ring-0 focus:border-none p-0" autocomplete="off">
        </form>
    </div>
</section>

<script>
function formatASCIITable(data) {
    if (!data || data.length === 0) return "Empty set (0 rows)";
    const keys = Object.keys(data[0]);
    const widths = {};
    keys.forEach(k => {
        widths[k] = k.length;
    });
    data.forEach(row => {
        keys.forEach(k => {
            const val = String(row[k] === null ? "NULL" : row[k]);
            if (val.length > widths[k]) {
                widths[k] = val.length;
            }
        });
    });
    
    let border = "+";
    keys.forEach(k => {
        border += "-".repeat(widths[k] + 2) + "+";
    });
    
    let result = border + "\n|";
    keys.forEach(k => {
        result += " " + k.padEnd(widths[k]) + " |";
    });
    result += "\n" + border + "\n";
    
    data.forEach(row => {
        result += "|";
        keys.forEach(k => {
            const val = String(row[k] === null ? "NULL" : row[k]);
            result += " " + val.padEnd(widths[k]) + " |";
        });
        result += "\n";
    });
    result += border;
    return result;
}

function executeManualQuery(e) {
    e.preventDefault();
    const input = document.getElementById('manual-query-input');
    const query = input.value.trim();
    if (!query) return;
    
    const logsContainer = document.getElementById('sql-logs-container');
    const noLogs = document.getElementById('no-logs-msg');
    if (noLogs) noLogs.remove();
    
    const time = new Date().toLocaleTimeString('en-US', { hour12: false });
    
    
    const cleanQuery = query.replace(/--.*/g, '').replace(/\/\*[\s\S]*?\*\
    const firstWordMatch = cleanQuery.match(/^[a-zA-Z]+/);
    const firstWord = firstWordMatch ? firstWordMatch[0].toLowerCase() : '';
    
    if (firstWord !== 'select' && firstWord !== 'insert' && firstWord !== 'update') {
        appendLogItem(time, query, "FAILED: Only SELECT, INSERT, or UPDATE queries are allowed.");
        input.value = '';
        return;
    }
    
    if (/\b(alter|drop|delete|truncate|grant|revoke)\b/i.test(cleanQuery)) {
        appendLogItem(time, query, "FAILED: Query contains forbidden keywords (ALTER, DROP, DELETE, etc.).");
        input.value = '';
        return;
    }
    
    fetch('ajax_query', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'query=' + encodeURIComponent(query)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            let detail = data.message;
            if (data.data) {
                const tableStr = formatASCIITable(data.data);
                detail += `\n<pre class="whitespace-pre overflow-x-auto text-xs my-2 text-green-500/90">${escapeHtml(tableStr)}</pre>`;
            }
            appendLogItem(time, query, "SUCCESS", detail);
        } else {
            appendLogItem(time, query, "FAILED: " + data.message);
        }
        input.value = '';
    })
    .catch(err => {
        appendLogItem(time, query, "FAILED: Connection error or invalid response");
        input.value = '';
    });
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function appendLogItem(time, query, status, detail = "") {
    const logsContainer = document.getElementById('sql-logs-container');
    const div = document.createElement('div');
    div.className = "mb-4 break-all";
    
    let statusClass = "text-green-500 font-bold";
    if (status.startsWith("FAILED")) {
        statusClass = "text-red-500 font-bold";
    }
    
    let htmlContent = `> [${time}] ${escapeHtml(query)} => <span class="${statusClass}">${status}</span>`;
    if (detail) {
        if (detail.trim().startsWith("<pre")) {
            htmlContent += `<div class="mt-1 pl-4 opacity-90">${detail}</div>`;
        } else {
            htmlContent += `<div class="mt-1 pl-4 text-xs opacity-75">${escapeHtml(detail)}</div>`;
        }
    }
    div.innerHTML = htmlContent;
    logsContainer.appendChild(div);
    logsContainer.scrollTop = logsContainer.scrollHeight;
}
</script>
