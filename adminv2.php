<?php
session_start();
$audioDir = __DIR__ . '/audio';
$pass = 'admin';
$timeoutMins = 43200;

if (!isset($_SESSION['admin_timeout_v2'])) {
    $_SESSION['admin_timeout_v2'] = $timeoutMins;
}
if (!isset($_SESSION['admin_login_time'])) {
    $_SESSION['admin_login_time'] = 0;
}

function redirect($msg) {
    $_SESSION['flash_message'] = $msg;
    header('Location: adminv2.php');
    exit;
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        if (($_POST['pass'] ?? '') === $pass) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            redirect('');
        } else {
            $_SESSION['flash_message'] = 'Wrong password.';
            header('Location: adminv2.php');
            exit;
        }
    }
    if ($_POST['action'] === 'logout') {
        session_destroy();
        session_start();
        $_SESSION['admin_timeout_v2'] = $timeoutMins;
        header('Location: adminv2.php');
        exit;
    }
}

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$loggedIn = !empty($_SESSION['admin_logged_in']);
$timeoutMins = $_SESSION['admin_timeout_v2'] ?? 43200;

$remaining = 0;
if ($loggedIn && $timeoutMins > 0) {
    $elapsed = time() - ($_SESSION['admin_login_time'] ?? 0);
    $remaining = ($timeoutMins * 60) - $elapsed;
    if ($remaining <= 0) {
        session_destroy();
        session_start();
        $_SESSION['admin_timeout_v2'] = 43200;
        $loggedIn = false;
        $remaining = 0;
        $_SESSION['flash_message'] = 'Session expired. Login again.';
        header('Location: adminv2.php');
        exit;
    }
}

$audioFiles = [];
if ($loggedIn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $a = $_POST['action'] ?? '';
        if ($a === 'upload' && !empty($_FILES['files'])) {
            $files = $_FILES['files']; $count = 0;
            foreach ($files['name'] as $i => $name) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (in_array($ext, ['mp3','wav','ogg','flac','aac','m4a','wma'])) {
                        move_uploaded_file($files['tmp_name'][$i], $audioDir . '/' . basename($name));
                        $count++;
                    }
                }
            }
            redirect("$count file(s) uploaded.");
        }
        if ($a === 'rename' && !empty($_POST['old']) && !empty($_POST['new'])) {
            $old = $audioDir . '/' . basename($_POST['old']);
            $new = $audioDir . '/' . basename($_POST['new']);
            if (file_exists($old) && !file_exists($new)) { rename($old, $new); redirect('File renamed.'); }
            else { redirect('Rename failed.'); }
        }
        if ($a === 'delete' && !empty($_POST['file'])) {
            $f = $audioDir . '/' . basename($_POST['file']);
            if (file_exists($f)) { unlink($f); redirect('File deleted.'); }
        }
        if ($a === 'delete_multi' && !empty($_POST['files'])) {
            $c = 0;
            foreach ($_POST['files'] as $f) {
                $p = $audioDir . '/' . basename($f);
                if (file_exists($p)) { unlink($p); $c++; }
            }
            redirect("$c file(s) deleted.");
        }
    }

    $files = glob($audioDir . '/*.{mp3,wav,ogg,flac,aac,m4a,wma}', GLOB_BRACE);
    foreach ($files as $f) {
        $audioFiles[] = ['name' => pathinfo($f, PATHINFO_FILENAME), 'filename' => basename($f), 'ext' => pathinfo($f, PATHINFO_EXTENSION), 'size' => filesize($f), 'date' => filemtime($f)];
    }
    usort($audioFiles, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audialis — Admin</title>
<style>
:root {
    --bg: #0f0f11; --surface: #1a1a1e; --surface2: #252529;
    --border: #333338; --text: #e8e8ed; --text2: #9a9aa0;
    --accent: #5b8def; --accent-hover: #7aa5ff; --danger: #e55555;
    --radius: 10px; --shadow: 0 2px 12px rgba(0,0,0,.4);
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg); color: var(--text); min-height: 100vh;
}
.container { max-width: 960px; margin: 0 auto; padding: 30px 20px 60px; }
header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 16px; margin-bottom: 28px;
}
h1 { font-size: 24px; font-weight: 700; }
h1 em { font-style: normal; color: var(--accent); font-weight: 300; }
h1 span { color: var(--accent); }
.back { color: var(--text2); text-decoration: none; font-size: 14px; }
.back:hover { color: var(--accent); }

.session-bar {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 10px 16px; margin-bottom: 20px;
    font-size: 13px;
}
.session-bar .label { color: var(--text2); }
.session-bar .time { color: var(--accent); font-weight: 600; font-variant-numeric: tabular-nums; }
.session-bar .logout-form { margin-left: auto; }
.session-bar .btn-logout {
    background: none; border: 1px solid var(--border); color: var(--text2);
    font-size: 12px; padding: 5px 12px; border-radius: 6px; cursor: pointer; transition: all .2s;
}
.session-bar .btn-logout:hover { border-color: var(--danger); color: var(--danger); }

.msg {
    padding: 10px 16px; border-radius: var(--radius); margin-bottom: 20px;
    font-size: 14px; background: var(--surface2); border: 1px solid var(--border);
}

.upload-zone {
    border: 2px dashed var(--border); border-radius: var(--radius);
    padding: 40px 20px; text-align: center; cursor: pointer;
    transition: all .25s; margin-bottom: 24px; background: var(--surface);
}
.upload-zone:hover, .upload-zone.dragover { border-color: var(--accent); background: var(--surface2); }
.upload-zone .icon { font-size: 36px; margin-bottom: 8px; }
.upload-zone p { color: var(--text2); font-size: 14px; }
.upload-zone .or { color: var(--text2); font-size: 12px; margin: 6px 0; }
.upload-btn {
    display: inline-block; padding: 8px 20px; background: var(--accent);
    color: #fff; border: none; border-radius: 7px; font-size: 13px;
    cursor: pointer; font-weight: 500; transition: background .2s;
}
.upload-btn:hover { background: var(--accent-hover); }

.toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 16px;
}
.toolbar .left { display: flex; align-items: center; gap: 10px; }
.count { color: var(--text2); font-size: 14px; }
.btn {
    padding: 7px 16px; border-radius: 7px; border: none; font-size: 13px;
    font-weight: 500; cursor: pointer; transition: all .2s;
}
.btn-del { background: var(--danger); color: #fff; }
.btn-del:hover { opacity: .85; }
.btn-del:disabled { opacity: .3; cursor: not-allowed; }
.btn-outline { background: none; border: 1px solid var(--border); color: var(--text2); }
.btn-outline:hover { border-color: var(--text); color: var(--text); }

table { width: 100%; border-collapse: collapse; font-size: 14px; }
th {
    text-align: left; padding: 10px 12px; color: var(--text2);
    font-weight: 500; font-size: 12px; text-transform: uppercase;
    border-bottom: 1px solid var(--border);
}
td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
tr:hover td { background: var(--surface); }
tr:last-child td { border-bottom: none; }
.col-check { width: 32px; }
.col-name { min-width: 160px; }
.col-size { width: 80px; color: var(--text2); }
.col-date { width: 140px; color: var(--text2); }
.col-actions { width: 120px; text-align: right; }
.file-name { font-weight: 500; }
.file-ext { color: var(--text2); text-transform: uppercase; font-size: 11px; }
.action-btn {
    background: none; border: none; cursor: pointer; font-size: 16px;
    padding: 4px 6px; border-radius: 4px; transition: all .2s;
    line-height: 1; color: var(--text2);
}
.action-btn:hover { color: var(--text); }
.action-btn.rename:hover { color: var(--accent); }
.action-btn.delete:hover { color: var(--danger); }

.modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7);
    z-index: 200; align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 24px; width: 90%; max-width: 400px;
    box-shadow: var(--shadow);
}
.modal h3 { margin-bottom: 16px; font-size: 17px; }
.modal p { color: var(--text2); font-size: 14px; margin-bottom: 16px; }
.modal input {
    width: 100%; padding: 10px 14px; border-radius: 7px;
    border: 1px solid var(--border); background: var(--surface2);
    color: var(--text); font-size: 14px; outline: none; margin-bottom: 12px;
}
.modal input:focus { border-color: var(--accent); }
.modal-actions { display: flex; gap: 8px; justify-content: flex-end; }
.modal-actions button { padding: 8px 20px; border-radius: 7px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; }
.modal-actions .cancel { background: var(--surface2); color: var(--text2); }
.modal-actions .cancel:hover { color: var(--text); }
.modal-actions .confirm { background: var(--accent); color: #fff; }
.modal-actions .confirm:hover { background: var(--accent-hover); }
.modal-actions .confirm-danger { background: var(--danger); color: #fff; }
.modal-actions .confirm-danger:hover { opacity: .85; }
.empty { text-align: center; padding: 50px 20px; color: var(--text2); }

@media (max-width: 600px) {
    .col-date { display: none; }
    .toolbar { flex-direction: column; align-items: stretch; }
}
</style>
</head>
<body>

<?php if ($loggedIn): ?>
<div class="container">
    <header>
        <div>
            <h1>♪ <span>Audialis</span> <em>Admin</em></h1>
            <a href="index.html" class="back">← Back to player</a>
        </div>
        <span class="count"><?= count($audioFiles) ?> file(s)</span>
    </header>

    <div class="session-bar">
        <span class="label">Session expires in</span>
        <span class="time" id="timer"><?= formatRemaining($remaining) ?></span>
        <form method="post" class="logout-form" style="margin:0">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="uploadForm">
        <input type="hidden" name="action" value="upload">
        <div class="upload-zone" id="dropZone">
            <div class="icon">🎵</div>
            <p>Drag & drop audio files here</p>
            <div class="or">— or —</div>
            <label class="upload-btn">Choose Files
                <input type="file" name="files[]" multiple accept=".mp3,.wav,.ogg,.flac,.aac,.m4a,.wma" style="display:none" id="fileInput">
            </label>
        </div>
    </form>

    <div class="toolbar">
        <div class="left">
            <button class="btn btn-del" id="deleteSelectedBtn" disabled onclick="confirmMultiDelete()">Delete Selected</button>
        </div>
        <button class="btn btn-outline" onclick="selectAll()">Select All</button>
    </div>

    <?php if (count($audioFiles) > 0): ?>
    <form method="post" id="multiForm">
        <input type="hidden" name="action" value="delete_multi">
        <table>
            <thead>
                <tr>
                    <th class="col-check"><input type="checkbox" id="selectAllCheck" onchange="toggleSelectAll()"></th>
                    <th class="col-name">Name</th>
                    <th class="col-size">Size</th>
                    <th class="col-date">Modified</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audioFiles as $f): ?>
                <tr>
                    <td><input type="checkbox" name="files[]" value="<?= htmlspecialchars($f['filename']) ?>" class="file-check" onchange="updateDeleteBtn()"></td>
                    <td>
                        <span class="file-name"><?= htmlspecialchars($f['name']) ?></span>
                        <span class="file-ext">.<?= $f['ext'] ?></span>
                    </td>
                    <td class="col-size"><?= formatSize($f['size']) ?></td>
                    <td class="col-date"><?= date('M j, Y g:i a', $f['date']) ?></td>
                    <td class="col-actions">
                        <button type="button" class="action-btn rename" onclick="openRename('<?= htmlspecialchars($f['filename'], ENT_QUOTES) ?>','<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>','<?= $f['ext'] ?>')" title="Rename">✎</button>
                        <button type="button" class="action-btn delete" onclick="openDelete('<?= htmlspecialchars($f['filename'], ENT_QUOTES) ?>')" title="Delete">✕</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
    <?php else: ?>
        <div class="empty"><p>No audio files yet. Drop some above!</p></div>
    <?php endif; ?>
</div>

<!-- Rename Modal -->
<div class="modal-overlay" id="renameModal"><div class="modal">
    <h3>Rename File</h3>
    <form method="post">
        <input type="hidden" name="action" value="rename">
        <input type="hidden" name="old" id="renameOld">
        <input type="text" name="new" id="renameNew" autocomplete="off">
        <div class="modal-actions">
            <button type="button" class="cancel" onclick="closeModal('renameModal')">Cancel</button>
            <button type="submit" class="confirm">Rename</button>
        </div>
    </form>
</div></div>

<!-- Delete Single Modal -->
<div class="modal-overlay" id="deleteModal"><div class="modal">
    <h3>Delete File</h3>
    <p id="deleteMsg">Are you sure?</p>
    <form method="post">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="file" id="deleteFile">
        <div class="modal-actions">
            <button type="button" class="cancel" onclick="closeModal('deleteModal')">Cancel</button>
            <button type="submit" class="confirm-danger">Delete</button>
        </div>
    </form>
</div></div>

<!-- Delete Multi Modal -->
<div class="modal-overlay" id="deleteMultiModal"><div class="modal">
    <h3>Delete Selected</h3>
    <p id="deleteMultiMsg">Delete the selected files?</p>
    <div class="modal-actions">
        <button type="button" class="cancel" onclick="closeModal('deleteMultiModal')">Cancel</button>
        <button type="button" class="confirm-danger" onclick="submitMultiDelete()">Delete All</button>
    </div>
</div></div>

<script>
let remaining = <?= $remaining ?>;
const timerEl = document.getElementById('timer');

function fmt(s) {
    if (s <= 0) return '0s';
    const d = Math.floor(s / 86400); s %= 86400;
    const h = Math.floor(s / 3600); s %= 3600;
    const m = Math.floor(s / 60); s = Math.floor(s % 60);
    let r = '';
    if (d > 0) r += d + 'd ';
    if (h > 0 || d > 0) r += h + 'h ';
    if (m > 0 || h > 0 || d > 0) r += m + 'm ';
    r += s + 's';
    return r;
}

function tick() {
    remaining--;
    if (remaining <= 0) { location.reload(); return; }
    timerEl.textContent = fmt(remaining);
}
timerEl.textContent = fmt(remaining);
setInterval(tick, 1000);

const zone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length) {
        const dt = new DataTransfer();
        for (const f of files) dt.items.add(f);
        fileInput.files = dt.files;
        document.getElementById('uploadForm').submit();
    }
});
zone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => { if (fileInput.files.length) document.getElementById('uploadForm').submit(); });

function toggleSelectAll() {
    const checked = document.getElementById('selectAllCheck').checked;
    document.querySelectorAll('.file-check').forEach(cb => cb.checked = checked);
    updateDeleteBtn();
}
function selectAll() {
    const all = document.querySelectorAll('.file-check');
    const some = Array.from(all).some(cb => !cb.checked);
    all.forEach(cb => cb.checked = some);
    document.getElementById('selectAllCheck').checked = some;
    updateDeleteBtn();
}
function updateDeleteBtn() {
    document.getElementById('deleteSelectedBtn').disabled = document.querySelectorAll('.file-check:checked').length === 0;
}
function openRename(filename, name, ext) {
    document.getElementById('renameOld').value = filename;
    document.getElementById('renameNew').value = name + '.' + ext;
    document.getElementById('renameModal').classList.add('open');
    setTimeout(() => document.getElementById('renameNew').select(), 100);
}
function openDelete(filename) {
    document.getElementById('deleteFile').value = filename;
    document.getElementById('deleteMsg').textContent = 'Delete "' + filename + '"?';
    document.getElementById('deleteModal').classList.add('open');
}
function confirmMultiDelete() {
    const checked = document.querySelectorAll('.file-check:checked').length;
    document.getElementById('deleteMultiMsg').textContent = 'Delete ' + checked + ' selected file(s)?';
    document.getElementById('deleteMultiModal').classList.add('open');
}
function submitMultiDelete() { document.getElementById('multiForm').submit(); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open')); });
</script>

<?php else: ?>
<div class="modal-overlay open" id="loginModal">
    <div class="modal">
        <h3>🔒 Audialis Login</h3>
        <?php if ($message): ?>
            <p style="color:var(--danger)"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="login">
            <input type="password" name="pass" placeholder="Enter password" autofocus>
            <div class="modal-actions">
                <a href="index.html" class="cancel" style="display:inline-flex;align-items:center;padding:8px 20px;border-radius:7px;border:none;font-size:13px;font-weight:500;cursor:pointer;background:var(--surface2);color:var(--text2);text-decoration:none">Back</a>
                <button type="submit" class="confirm">Login</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>
<?php
function formatSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024) . ' KB';
    return $bytes . ' B';
}
function formatRemaining($secs) {
    if ($secs <= 0) return '0s';
    $d = floor($secs / 86400); $secs %= 86400;
    $h = floor($secs / 3600); $secs %= 3600;
    $m = floor($secs / 60); $s = $secs % 60;
    $r = '';
    if ($d > 0) $r .= $d . 'd ';
    if ($h > 0 || $d > 0) $r .= $h . 'h ';
    if ($m > 0 || $h > 0 || $d > 0) $r .= $m . 'm ';
    $r .= $s . 's';
    return $r;
}
?>
