<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/admin-users.css?v=<?php echo time(); ?>">
    <title>
        <?php echo $pageTitle; ?>
    </title>
</head>

<div class="admin-layout">
    <?php include __DIR__ . '/fragments/sidebar.php'; ?>

    <div class="main-content">



        <!-- Main Container -->
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-info">
                    <h2>User Management</h2>
                    <p>Manage and monitor system users across all roles</p>
                </div>

            </div>

            <!-- Filter Section -->
            <div class="filter-section"
                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
               <div class="filter-tabs" id="userTypeTabs">
                    <button class="filter-tab active" data-type="3">
                        <i class="bi bi-people-fill me-1"></i> Customers
                    </button>

                    <button class="filter-tab" data-type="1">
                        <i class="bi bi-shield-lock-fill me-1"></i> Admin
                    </button>

                    <button class="filter-tab" data-type="2">
                        <i class="bi bi-person-badge-fill me-1"></i> Employees
                    </button>
                </div>



                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by Name, Email, Phone...">
                    <i class="bi bi-search"></i>
                </div>
            </div>

            <!-- Users Table -->
            <div class="users-table-container">
                <div class="table-wrapper">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php if (empty($users)): ?>
                                <tr id="userEmptyState">
                                    <td colspan="6" style="text-align: center;">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $userTypeName = '';
                                    if ($user['userTypeID'] == 1)
                                        $userTypeName = 'Admin';
                                    else if ($user['userTypeID'] == 2)
                                        $userTypeName = 'Employee';
                                    else if ($user['userTypeID'] == 3)
                                        $userTypeName = 'Customer';
                                    ?>
                                    <tr data-user-id="<?php echo $user['userID']; ?>"
                                        data-user-type="<?php echo $user['userTypeID']; ?>">
                                        <td>#USER-<?php echo str_pad($user['userID'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?>
                                            <?php if (!empty($user['isBlocked'])): ?>
                                                <span style="display:inline-block; margin-left:6px; background:#fee2e2; color:#dc2626; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px; letter-spacing:0.5px; vertical-align:middle;">BLOCKED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phoneNumber']); ?></td>
                                        <td>
                                            <div class="action-dropdown">
                                                <button class="action-trigger">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <div class="action-menu">
    <button class="action-item view" 
            onclick="viewUser(<?php echo $user['userID']; ?>, <?php echo $user['userTypeID']; ?>)">
        <i class="bi bi-eye"></i>
        <span>View Details</span>
    </button>

    <?php if ($user['userTypeID'] != 3): ?>
        <button class="action-item edit" 
                onclick="editUser(<?php echo $user['userID']; ?>, <?php echo $user['userTypeID']; ?>)">
            <i class="bi bi-pencil"></i>
            <span>Update User</span>
        </button>
    <?php endif; ?>

    <?php if ($user['userTypeID'] == 3): ?>
        <?php if (empty($user['isBlocked'])): ?>
        <button class="action-item" style="color:#dc2626;"
                onclick="confirmBlockUser(<?php echo $user['userID']; ?>, '<?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?>', 'block')">
            <i class="bi bi-slash-circle"></i>
            <span>Block User</span>
        </button>
        <?php else: ?>
        <button class="action-item" style="color:#16a34a;"
                onclick="confirmBlockUser(<?php echo $user['userID']; ?>, '<?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?>', 'unblock')">
            <i class="bi bi-check-circle"></i>
            <span>Unblock User</span>
        </button>
        <?php endif; ?>
    <?php endif; ?>
</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="userEmptyState" style="display: none;">
                                <td colspan="5" style="text-align: center;">No users match your filters</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- --- PAGINATION CONTROLS --- -->
                <?php if ($totalRows > $limit): ?>
                <div class="pagination-container mt-4">
                    <div class="pagination-info">
                        Showing <?php echo count($users); ?> of <?php echo $totalRows; ?> total users
                    </div>
                    <div class="pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>" class="pagination-btn">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        <?php else: ?>
                            <button class="pagination-btn disabled" disabled>
                                <i class="bi bi-chevron-left"></i> Previous
                            </button>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1): ?>
                                <a href="?page=1&limit=<?php echo $limit; ?>" class="pagination-number">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>"
                                    class="pagination-number <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>"
                                    class="pagination-number"><?php echo $totalPages; ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>" class="pagination-btn">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <button class="pagination-btn disabled" disabled>
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- View User Modal -->
        <div id="viewModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h3><i class="bi bi-eye"></i> User Details</h3>
                    <button class="modal-close" onclick="closeViewModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>User ID</label>
                            <input type="text" id="viewUserId" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" id="viewUserName" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" id="viewFirstName" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" id="viewLastName" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" id="viewMiddleName" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <input type="text" id="viewGender" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" id="viewEmail" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" id="viewPhone" class="form-control" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div id="addModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h3><i class="bi bi-person-plus"></i> Add New User</h3>
                    <button class="modal-close" onclick="closeAddModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addUserName">Username *</label>
                            <input type="text" id="addUserName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="addPassword">Password *</label>
                            <input type="password" id="addPassword" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addFirstName">First Name *</label>
                            <input type="text" id="addFirstName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="addLastName">Last Name *</label>
                            <input type="text" id="addLastName" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addMiddleName">Middle Name</label>
                            <input type="text" id="addMiddleName" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="addBirthday">Birthday *</label>
                            <input type="date" id="addBirthday" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addGender">Gender *</label>
                            <select id="addGender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="addUserType">User Type *</label>
                            <select id="addUserType" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="1">Admin</option>
                                <option value="2">Employee</option>
                                <option value="3">Customer</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addEmail">Email *</label>
                            <input type="email" id="addEmail" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="addPhone">Phone Number *</label>
                            <input type="tel" id="addPhone" class="form-control" maxlength="11"
                                placeholder="09XXXXXXXXX" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button class="btn btn-save" onclick="saveNewUser()">
                        <i class="bi bi-check-circle"></i> Add User
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h3><i class="bi bi-pencil-square"></i> Update User</h3>
                    <button class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editUserId">User ID</label>
                            <input type="text" id="editUserId" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label for="editUserName">Username *</label>
                            <input type="text" id="editUserName" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editFirstName">First Name *</label>
                            <input type="text" id="editFirstName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="editLastName">Last Name *</label>
                            <input type="text" id="editLastName" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editMiddleName">Middle Name</label>
                            <input type="text" id="editMiddleName" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="editBirthday">Birthday *</label>
                            <input type="date" id="editBirthday" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editGender">Gender *</label>
                            <select id="editGender" class="form-control" required>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editUserType">User Type *</label>
                            <select id="editUserType" class="form-control" required>
                                <option value="1">Admin</option>
                                <option value="2">Employee</option>
                                <option value="3">Customer</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editEmail">Email *</label>
                            <input type="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="editPhone">Phone Number *</label>
                            <input type="tel" id="editPhone" class="form-control" maxlength="11" required inputmode="numeric" pattern="[0-9]*" placeholder="09XXXXXXXXX" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button class="btn btn-save" onclick="saveUserUpdate()">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>

<div id="statusModal" class="modal-overlay">
    <div class="modal status-modal-content" style="max-width: 400px; text-align: center;">
        <div class="modal-body">
            <div id="statusIcon" style="font-size: 4rem; margin-bottom: 15px;"></div>
            <h3 id="statusTitle" style="margin-bottom: 10px;"></h3>
            <p id="statusMessage" style="color: #666; margin-bottom: 25px;"></p>
            <button class="btn btn-save" style="width: 100%;" onclick="closeStatusModal()">
                Continue
            </button>
        </div>
    </div>
</div>

        <?php include __DIR__ . '/fragments/scripts.php'; ?>

        <script src="../js/users_management.js?v=<?php echo time(); ?>"></script>

        <!-- Block/Unblock Confirmation Modal -->
        <div id="blockModal" class="modal-overlay">
            <div class="confirm-modal-content" style="max-width:460px; border-radius:24px; padding:0; overflow:hidden; width:100%;">
                <div style="background:#f8fafc; padding:22px 28px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div id="blockModalIcon" style="width:40px; height:40px; flex-shrink:0; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(220,38,38,0.1); border:1px solid rgba(220,38,38,0.2);">
                            <i class="bi bi-slash-circle" style="color:#dc2626; font-size:1.2rem;"></i>
                        </div>
                        <div>
                            <h4 id="blockModalTitle" style="margin:0; color:var(--primary); font-size:1.05rem; font-weight:800; letter-spacing:-0.3px;">Block User</h4>
                            <p style="margin:2px 0 0 0; color:#64748b; font-size:0.75rem; font-weight:500;">This action will restrict the user's access</p>
                        </div>
                    </div>
                </div>
                <div style="padding:24px 28px; background:#fff;">
                    <p id="blockModalMessage" style="margin:0; color:#475569; font-size:0.9rem; line-height:1.65;"></p>
                </div>
                <div style="padding:16px 28px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; gap:10px;">
                    <button onclick="closeBlockModal()" style="flex:1; border-radius:10px; padding:11px 0; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.5px; border:none; cursor:pointer; background:#f1f5f9; color:#475569;">CANCEL</button>
                    <button id="blockModalConfirmBtn" onclick="executeBlockAction()" style="flex:1; border-radius:10px; padding:11px 0; font-weight:700; font-size:0.88rem; text-transform:uppercase; letter-spacing:0.5px; border:none; cursor:pointer; background:#dc2626; color:#fff;">YES, BLOCK USER</button>
                </div>
            </div>
        </div>

        <script>
        let _blockTarget = { id: null, action: null };

        function confirmBlockUser(customerID, name, action) {
            _blockTarget = { id: customerID, action: action };
            const isBlock = action === 'block';
            const icon = document.getElementById('blockModalIcon');
            const title = document.getElementById('blockModalTitle');
            const msg = document.getElementById('blockModalMessage');
            const btn = document.getElementById('blockModalConfirmBtn');

            if (isBlock) {
                icon.style.background = 'rgba(220,38,38,0.1)';
                icon.style.borderColor = 'rgba(220,38,38,0.2)';
                icon.innerHTML = '<i class="bi bi-slash-circle" style="color:#dc2626; font-size:1.2rem;"></i>';
                title.textContent = 'Block User';
                msg.textContent = `Are you sure you want to block "${name}"? They will immediately lose access to their account and will not be able to log in.`;
                btn.textContent = 'YES, BLOCK USER';
                btn.style.background = '#dc2626';
            } else {
                icon.style.background = 'rgba(22,163,74,0.1)';
                icon.style.borderColor = 'rgba(22,163,74,0.2)';
                icon.innerHTML = '<i class="bi bi-check-circle" style="color:#16a34a; font-size:1.2rem;"></i>';
                title.textContent = 'Unblock User';
                msg.textContent = `Are you sure you want to unblock "${name}"? They will regain full access to their account.`;
                btn.textContent = 'YES, UNBLOCK USER';
                btn.style.background = '#16a34a';
            }

            document.getElementById('blockModal').classList.add('active');
        }

        function closeBlockModal() {
            document.getElementById('blockModal').classList.remove('active');
            _blockTarget = { id: null, action: null };
        }

        function executeBlockAction() {
            if (!_blockTarget.id) return;
            const fd = new FormData();
            fd.append('action', _blockTarget.action === 'block' ? 'block_user' : 'unblock_user');
            fd.append('customerID', _blockTarget.id);

            fetch('../backend/user_action_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    closeBlockModal();
                    if (data.success) {
                        // Reload page to reflect updated state
                        location.reload();
                    } else {
                        alert(data.message || 'Action failed.');
                    }
                })
                .catch(() => alert('Network error. Please try again.'));
        }
        </script>
    </div> <!-- main-content -->
</div> <!-- admin-layout -->
</body>

</html>