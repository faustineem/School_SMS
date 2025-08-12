<?php
// Role-based permissions and utilities

function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = $_SESSION['role'];
    
    $permissions = [
        'admin' => [
            'manage_users', 'manage_students', 'manage_teachers', 'manage_parents',
            'view_reports', 'link_parent', 'view_all_results', 'view_all_fees',
            'send_messages', 'assign_advisor'
        ],
        'teacher' => [
            'enter_marks', 'view_classes', 'view_assigned_students', 'view_results',
            'send_messages'
        ],
        'student' => [
            'view_profile', 'view_results', 'view_fees', 'view_messages'
        ],
        'parent' => [
            'view_child_profile', 'view_child_results', 'view_child_fees', 'view_child_messages'
        ]
    ];
    
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}

function getRoleDisplayName($role) {
    $roles = [
        'admin' => 'Administrator',
        'teacher' => 'Teacher',
        'student' => 'Student',
        'parent' => 'Parent'
    ];
    
    return $roles[$role] ?? 'Unknown';
}

function getMenuItems($role) {
    $menus = [
        'admin' => [
            ['title' => 'Dashboard', 'url' => '/dashboard.php', 'icon' => 'home'],
            ['title' => 'Manage Students', 'url' => '/admin/manage_students.php', 'icon' => 'users'],
            ['title' => 'Manage Teachers', 'url' => '/admin/manage_teachers.php', 'icon' => 'user-check'],
            ['title' => 'Manage Parents', 'url' => '/admin/manage_parents.php', 'icon' => 'user-plus'],
            ['title' => 'Reports', 'url' => '/admin/reports.php', 'icon' => 'file-text'],
            ['title' => 'Link Parent', 'url' => '/admin/link_parent.php', 'icon' => 'link'],
            ['title' => 'Messages', 'url' => '/shared/messages.php', 'icon' => 'message-square']
        ],
        'teacher' => [
            ['title' => 'Dashboard', 'url' => '/teacher/dashboard.php', 'icon' => 'home'],
            ['title' => 'Enter Marks', 'url' => '/teacher/enter_marks.php', 'icon' => 'edit'],
            ['title' => 'View Classes', 'url' => '/teacher/view_classes.php', 'icon' => 'users'],
            ['title' => 'My Students', 'url' => '/teacher/my_students.php', 'icon' => 'user'],
            ['title' => 'Messages', 'url' => '/shared/messages.php', 'icon' => 'message-square']
        ],
        'student' => [
            ['title' => 'Dashboard', 'url' => '/student/dashboard.php', 'icon' => 'home'],
            ['title' => 'View Results', 'url' => '/student/results.php', 'icon' => 'award'],
            ['title' => 'Fee Status', 'url' => '/student/fees.php', 'icon' => 'credit-card'],
            ['title' => 'Messages', 'url' => '/student/messages.php', 'icon' => 'message-square']
        ],
        'parent' => [
            ['title' => 'Dashboard', 'url' => '/parent/dashboard.php', 'icon' => 'home'],
            ['title' => 'Child Information', 'url' => '/parent/child_info.php', 'icon' => 'user'],
            ['title' => 'Child Results', 'url' => '/parent/child_results.php', 'icon' => 'award'],
            ['title' => 'Child Fees', 'url' => '/parent/child_fees.php', 'icon' => 'credit-card'],
            ['title' => 'Messages', 'url' => '/parent/child_messages.php', 'icon' => 'message-square']
        ]
    ];
    
    return $menus[$role] ?? [];
}
?>
