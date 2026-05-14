<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit;
}
$current_admin_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard – FarmConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fff5eb; padding: 2rem; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 2rem; gap: 1rem; }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.5rem; font-weight: 700; color: #C62828; }
        .logo i { font-size: 2rem; color: #F9A825; }
        .logout-btn { background: #C62828; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 60px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .logout-btn:hover { background: #B71C1C; }
        #changePasswordBtn { background: #F9A825; color: #5D2906; margin-right: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 24px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border: 1px solid #FFE0B2; transition: 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(198,40,40,0.1); }
        .stat-title { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; color: #C62828; margin-bottom: 0.5rem; }
        .stat-number { font-size: 2.5rem; font-weight: 700; color: #5D2906; }
        .search-bar { margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; }
        .search-bar input { flex: 1; padding: 0.8rem 1rem; border: 1px solid #FFE0B2; border-radius: 60px; background: #FEF9F0; font-size: 1rem; }
        .search-bar button { background: #C62828; color: white; border: none; padding: 0 1.5rem; border-radius: 60px; cursor: pointer; font-weight: 600; }
        .btn-outline { background: #FFF3E0 !important; color: #C62828 !important; border: 1px solid #FFE0B2 !important; }
        .users-section { background: white; border-radius: 24px; padding: 1.5rem; border: 1px solid #FFE0B2; overflow-x: auto; }
        .users-section h2 { font-size: 1.5rem; margin-bottom: 1rem; color: #C62828; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #FFE0B2; }
        th { background: #FFF3E0; color: #5D2906; font-weight: 600; position: sticky; top: 0; }
        tr:hover { background: #FFF8F0; }
        .badge-admin { background: #F9A825; color: #5D2906; padding: 4px 12px; border-radius: 60px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-user { background: #E0E0E0; color: #5D2906; padding: 4px 12px; border-radius: 60px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .btn-delete { background: #C62828; color: white; border: none; padding: 6px 12px; border-radius: 40px; cursor: pointer; font-size: 0.8rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; }
        .btn-delete:hover { background: #B71C1C; transform: scale(0.98); }
        .loading { text-align: center; padding: 2rem; color: #C62828; }
        @media (max-width: 768px) { body { padding: 1rem; } .stat-number { font-size: 1.8rem; } th, td { padding: 8px 6px; font-size: 0.8rem; } .btn-delete { padding: 4px 8px; font-size: 0.7rem; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">
       
           
            <img src="Pic/Copilot_20260427_022306.png" alt="" height="150px" width="250px" style="object-fit: contain;"> <span style="font-size:70px;">Admin</span>
        </div>
        <div>
            <button id="changePasswordBtn" class="logout-btn" style="background:#F9A825; color:#5D2906; margin-right:1rem;"><i class="fas fa-key"></i> Change Password</button>
            <form method="post" action="logout.php" style="display:inline;">
                <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </div>
    </div>

    <div class="stats-grid" id="statsGrid">
        <div class="stat-card"><div class="stat-title">Total Users</div><div class="stat-number" id="totalUsers">-</div></div>
        <div class="stat-card"><div class="stat-title">Total Listings</div><div class="stat-number" id="totalListings">-</div></div>
        <div class="stat-card"><div class="stat-title">Total Buyers</div><div class="stat-number" id="totalBuyers">-</div></div>
        <div class="stat-card"><div class="stat-title">Total Inquiries</div><div class="stat-number" id="totalInquiries">-</div></div>
    </div>

    <div class="users-section">
        <h2><i class="fas fa-users"></i> Manage Users</h2>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Search by username, name, or email...">
            <button id="searchBtn">Search</button>
            <button id="resetBtn" class="btn-outline">Reset</button>
        </div>
        <div id="usersTableContainer">
            <div class="loading">Loading users...</div>
        </div>
        <div style="margin-top: 3rem;">
    <h2><i class="fas fa-comment-dots"></i> User Feedback</h2>
    <div id="feedbackContainer">
        <div class="loading">Loading feedback...</div>
    </div>
</div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:24px; padding:2rem; max-width:450px; width:90%; border-top:6px solid #F9A825;">
        <h3 style="color:#C62828; margin-bottom:1rem;"><i class="fas fa-key"></i> Change Password</h3>
        <form id="changePasswordForm">
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Current Password</label>
                <input type="password" id="current_password" required style="width:100%; padding:0.8rem; border:1px solid #FFE0B2; border-radius:60px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600;">New Password (min 6 chars)</label>
                <input type="password" id="new_password" required style="width:100%; padding:0.8rem; border:1px solid #FFE0B2; border-radius:60px;">
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Confirm New Password</label>
                <input type="password" id="confirm_password" required style="width:100%; padding:0.8rem; border:1px solid #FFE0B2; border-radius:60px;">
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <button type="button" id="cancelPasswordBtn" style="background:#E0E0E0; border:none; padding:0.6rem 1.2rem; border-radius:60px;">Cancel</button>
                <button type="submit" style="background:#C62828; color:white; border:none; padding:0.6rem 1.2rem; border-radius:60px;">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    const CURRENT_ADMIN_ID = <?php echo json_encode($current_admin_id); ?>;
    let currentSearch = '';

    async function fetchStats() {
        const formData = new FormData();
        formData.append('action', 'getStats');
        const res = await fetch('admin_handler.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            document.getElementById('totalUsers').innerText = data.stats.totalUsers;
            document.getElementById('totalListings').innerText = data.stats.totalListings;
            document.getElementById('totalBuyers').innerText = data.stats.totalBuyers;
            document.getElementById('totalInquiries').innerText = data.stats.totalInquiries;
        }
    }

    async function fetchUsers() {
        const formData = new FormData();
        formData.append('action', 'getUsers');
        if (currentSearch) formData.append('search', currentSearch);
        const res = await fetch('admin_handler.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) renderUsersTable(data.users);
        else document.getElementById('usersTableContainer').innerHTML = `<div class="loading">Error loading users: ${data.error}</div>`;
    }

    function renderUsersTable(users) {
        if (!users.length) {
            document.getElementById('usersTableContainer').innerHTML = '<div class="loading">No users found.</div>';
            return;
        }
        let html = `<table>
            <thead>
                <tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Listings</th><th>Role</th><th>Action</th></tr>
            </thead>
            <tbody>`;
        users.forEach(user => {
            const isAdmin = parseInt(user.is_admin) === 1;
            const roleBadge = isAdmin ? '<span class="badge-admin">Admin</span>' : '<span class="badge-user">User</span>';
            const showDelete = (user.user_id != CURRENT_ADMIN_ID);
            const deleteButton = showDelete ? `<button class="btn-delete" data-id="${user.user_id}"><i class="fas fa-trash"></i> Delete</button>` : '—';
            html += `<tr>
                <td>${user.user_id}</td>
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.fullname || '-')}</td>
                <td>${escapeHtml(user.email || '-')}</td>
                <td>${user.listing_count}</td>
                <td>${roleBadge}</td>
                <td>${deleteButton}</td>
            </tr>`;
        });
        html += `</tbody>${'</table>'}`;
        document.getElementById('usersTableContainer').innerHTML = html;

        // Attach delete events
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', async () => {
                const userId = btn.getAttribute('data-id');
                if (!confirm('⚠️ Delete this user? All their listings, inquiries, messages will be permanently removed. This cannot be undone.')) return;
                const fd = new FormData();
                fd.append('action', 'deleteUser');
                fd.append('user_id', userId);
                try {
                    const res = await fetch('admin_handler.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) {
                        alert('User deleted successfully');
                        fetchUsers();
                        fetchStats();
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch (err) {
                    alert('Network error: ' + err.message);
                }
            });
        });
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    }

    document.getElementById('searchBtn').addEventListener('click', () => {
        currentSearch = document.getElementById('searchInput').value.trim();
        fetchUsers();
    });
    document.getElementById('resetBtn').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        currentSearch = '';
        fetchUsers();
    });
    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            currentSearch = document.getElementById('searchInput').value.trim();
            fetchUsers();
        }
    });

    // Change Password Modal
    const modal = document.getElementById('passwordModal');
    const changeBtn = document.getElementById('changePasswordBtn');
    const cancelBtn = document.getElementById('cancelPasswordBtn');
    const passwordForm = document.getElementById('changePasswordForm');
    changeBtn.addEventListener('click', () => modal.style.display = 'flex');
    cancelBtn.addEventListener('click', () => { modal.style.display = 'none'; passwordForm.reset(); });
    window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const current = document.getElementById('current_password').value;
        const newPass = document.getElementById('new_password').value;
        const confirm = document.getElementById('confirm_password').value;
        const fd = new FormData();
        fd.append('action', 'changeAdminPassword');
        fd.append('current_password', current);
        fd.append('new_password', newPass);
        fd.append('confirm_password', confirm);
        const res = await fetch('admin_handler.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert('Password changed successfully! Please log in again.');
            modal.style.display = 'none';
            passwordForm.reset();
            setTimeout(() => { window.location.href = 'logout.php'; }, 1500);
        } else {
            alert('Error: ' + data.error);
        }
    });

async function fetchFeedback() {
    const formData = new FormData();
    formData.append('action', 'getFeedback');
    const res = await fetch('admin_handler.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
        renderFeedback(data.feedback);
    } else {
        document.getElementById('feedbackContainer').innerHTML = '<div class="loading">Error loading feedback</div>';
    }
}

function renderFeedback(feedback) {
    const container = document.getElementById('feedbackContainer');
    if (!feedback.length) {
        container.innerHTML = '<div class="loading">No feedback yet.</div>';
        return;
    }
    let html = `<table style="margin-top:1rem;">
        <thead><tr><th>User</th><th>Message</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>`;
    feedback.forEach(fb => {
        html += `<tr>
            <td>${escapeHtml(fb.username)} (ID: ${fb.user_id})</td>
            <td>${escapeHtml(fb.message)}</td>
            <td>${new Date(fb.created_at).toLocaleString()}</td>
            <td><button class="btn-delete" data-feedback-id="${fb.feedback_id}"><i class="fas fa-trash"></i> Delete</button></td>
        </tr>`;
    });
    html += `</tbody></table>`;
    container.innerHTML = html;

    // attach delete events for feedback
    document.querySelectorAll('[data-feedback-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const feedbackId = btn.getAttribute('data-feedback-id');
            if (!confirm('Delete this feedback?')) return;
            const fd = new FormData();
            fd.append('action', 'deleteFeedback');
            fd.append('feedback_id', feedbackId);
            const res = await fetch('admin_handler.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                fetchFeedback(); // refresh list
            } else {
                alert('Error: ' + data.error);
            }
        });
    });
}

// Call fetchFeedback after loading users (inside the initialisation)
fetchFeedback();

    fetchStats();
    fetchUsers();
</script>
</body>
</html>