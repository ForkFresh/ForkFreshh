<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle).' – ForkFresh Admin' : 'Admin – ForkFresh' ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root{--green:#2e7d32;--green-dark:#1b5e20;--orange:#f57c00;--sidebar-w:220px;--border:#e0e0e0;--bg:#f4f4f4;--white:#fff;--radius:10px;--shadow:0 2px 12px rgba(0,0,0,.07);}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:#1a1a1a;min-height:100vh;display:flex;}
    a{text-decoration:none;color:inherit;}
    ul{list-style:none;}
    button{font-family:inherit;cursor:pointer;}
    input,textarea,select{font-family:inherit;}
    /* Sidebar */
    .admin-sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:200;transition:transform .25s;}
    .admin-sidebar-logo{padding:20px 20px 14px;border-bottom:1px solid var(--border);}
    .admin-nav{flex:1;padding:12px 10px;overflow-y:auto;display:flex;flex-direction:column;}
    .admin-nav-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-size:.88rem;font-weight:500;color:#555;margin-bottom:2px;transition:background .2s,color .2s;}
    .admin-nav-item i{width:18px;text-align:center;font-size:.95rem;}
    .admin-nav-item:hover{background:#f0f7f0;color:var(--green);}
    .admin-nav-item.active{background:var(--green);color:#fff;font-weight:600;}
    .admin-nav-item.active i{color:#fff;}
    .admin-logout{color:#c62828;margin-top:auto;}
    .admin-logout:hover{background:#fdecea;color:#b71c1c;}
    /* Main */
    .admin-main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-width:0;}
    .admin-topbar{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;background:var(--white);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
    .admin-topbar h1{font-size:1.15rem;font-weight:700;}
    .admin-topbar-right{display:flex;align-items:center;gap:14px;}
    .admin-avatar{width:36px;height:36px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;}
    .admin-content{padding:24px 28px;flex:1;}
    /* Cards */
    .stat-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
    .stat-card-a{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);padding:18px 20px;box-shadow:var(--shadow);}
    .stat-card-a .sc-icon{width:42px;height:42px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:12px;}
    .stat-card-a .sc-value{font-size:1.6rem;font-weight:800;margin-bottom:3px;}
    .stat-card-a .sc-label{font-size:.78rem;color:#888;}
    .sc-green{background:#e8f5e9;color:var(--green);}
    .sc-orange{background:#fff3e0;color:var(--orange);}
    .sc-blue{background:#e3f2fd;color:#1565c0;}
    .sc-purple{background:#f3e5f5;color:#7b1fa2;}
    /* Table */
    .admin-table-wrap{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;box-shadow:var(--shadow);}
    .admin-table-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);}
    .admin-table-head h2{font-size:.95rem;font-weight:700;}
    table{width:100%;border-collapse:collapse;font-size:.85rem;}
    th{padding:10px 16px;text-align:left;color:#888;font-weight:600;background:#fafafa;border-bottom:1px solid var(--border);}
    td{padding:12px 16px;border-bottom:1px solid #f0f0f0;color:#444;}
    tr:last-child td{border-bottom:none;}
    tr:hover td{background:#fafafa;}
    /* Badges */
    .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:capitalize;}
    .badge-green{background:#e8f5e9;color:#2e7d32;}
    .badge-orange{background:#fff3e0;color:#f57c00;}
    .badge-red{background:#fdecea;color:#c62828;}
    .badge-blue{background:#e3f2fd;color:#1565c0;}
    .badge-gray{background:#f5f5f5;color:#666;}
    /* Buttons */
    .btn-sm{padding:6px 14px;border-radius:6px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;transition:background .2s;}
    .btn-primary{background:var(--green);color:#fff;}.btn-primary:hover{background:var(--green-dark);}
    .btn-danger{background:#e53935;color:#fff;}.btn-danger:hover{background:#b71c1c;}
    .btn-outline-sm{background:#fff;border:1.5px solid var(--border);color:#444;}.btn-outline-sm:hover{border-color:var(--green);color:var(--green);}
    /* Form */
    .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
    .form-group{margin-bottom:14px;}
    .form-group label{display:block;font-size:.82rem;font-weight:600;margin-bottom:5px;color:#555;}
    .form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:.88rem;outline:none;transition:border-color .2s;}
    .form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--green);}
    .form-group textarea{resize:vertical;min-height:80px;}
    .alert-ok{background:#e8f5e9;color:#2e7d32;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.88rem;}
    .alert-err{background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.88rem;}
    /* Sidebar toggle */
    .sidebar-toggle-btn{display:none;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#555;padding:4px 8px;}
    .admin-sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:199;}
    .admin-sidebar-overlay.visible{display:block;}
    @media(max-width:1024px){.stat-cards{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:768px){
      .admin-sidebar{transform:translateX(-100%);}
      .admin-sidebar.open{transform:translateX(0);}
      .admin-main{margin-left:0;}
      .sidebar-toggle-btn{display:block;}
      .stat-cards{grid-template-columns:1fr 1fr;}
      .admin-content{padding:16px;}
      .form-grid-2{grid-template-columns:1fr;}
    }
    @media(max-width:480px){.stat-cards{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="admin-sidebar-overlay" id="adminOverlay"></div>
<div class="admin-main">
  <header class="admin-topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="sidebar-toggle-btn" id="adminToggle" aria-label="Menu"><i class="fa fa-bars"></i></button>
      <h1><?= isset($pageTitle) ? e($pageTitle) : 'Admin' ?></h1>
    </div>
    <div class="admin-topbar-right">
      <a href="<?= BASE_URL ?>index.php" style="font-size:.83rem;color:#666;"><i class="fa fa-store"></i> View Site</a>
      <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 2)) ?></div>
    </div>
  </header>
  <div class="admin-content">
