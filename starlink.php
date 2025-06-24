<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

require 'db.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Starlink Diagnostics</title>
  <link rel="stylesheet" href="starlink-dashboard.css" />
  <style>
    body {
      margin: 0;
      background-color: #2f2f2f;
      color: white;
      font-family: Arial, sans-serif;
    }

    .top-bar {
      background-color: #000;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .left-logo {
      display: flex;
      align-items: center;
    }

    .logo {
      height: 24px;
      margin-right: 8px;
    }

    .logo-text {
      font-weight: bold;
      letter-spacing: 1px;
    }

    .top-right {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .account-btn {
      background-color: #f1f1f7;
      border: none;
      padding: 6px 16px;
      font-size: 12px;
      font-weight: bold;
      border-radius: 6px;
    }

    .logout-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #2f2f2f;
      border: 2px solid #ff3b30;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      text-decoration: none;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .logout-icon {
      width: 20px;
      height: 20px;
    }

    .logout-btn:hover {
      background-color: #ff3b30;
      transform: scale(1.1);
    }

    .logout-btn:hover .logout-icon {
      filter: brightness(0) invert(1);
    }

    .dashboard {
      display: flex;
    }

    .sidebar {
      width: 220px;
      background-color: #000;
      padding: 20px;
      height: auto;
    }

    .sidebar h3 {
      font-size: 16px;
      margin-bottom: 10px;
      color: #00f38d;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar li {
      padding: 10px;
      margin-bottom: 5px;
    }

    .sidebar li a {
      text-decoration: none;
      color: white;
      display: block;
      border-radius: 4px;
    }

    .sidebar li.active a,
    .sidebar li a:hover {
      background-color: #00f38d;
      color: black;
      font-weight: bold;
    }

    .main-content {
      flex: 1;
      padding: 20px;
    }

    h1 {
      font-size: 24px;
      margin-bottom: 20px;
    }

    .starlink-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .diagnostic-card {
      background: #1a1a1a;
      color: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.5);
      text-align: center;
    }

    .diagnostic-card h4 {
      margin: 0 0 8px;
      font-size: 0.9rem;
      font-weight: 600;
      color: #9beeff;
    }

    .diagnostic-card p {
      font-size: 1.2rem;
      font-weight: bold;
      color: #ffffff;
      word-break: break-word;
    }

    #menu-toggle {
      display: none;
      font-size: 24px;
      background: none;
      border: none;
      color: white;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      #menu-toggle {
        display: block;
      }

      #mobile-sidebar {
        display: none;
        position: absolute;
        top: 60px;
        left: 0;
        background-color: black;
        width: 100%;
        z-index: 999;
      }

      #mobile-sidebar.show-sidebar {
        display: block;
      }

      .starlink-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <header class="top-bar">
    <div class="left-logo">
      <img src="./assets/logo white.png" alt="Logo" class="logo" />
      <span class="logo-text">INTELSAT</span>
    </div>
    <div class="top-right">
      <button class="account-btn"><?= strtoupper($username) ?></button>
      <div id="menu-toggle">&#9776;</div>
      <a href="logout.php" class="logout-btn" title="Logout">
        <img src="./assets/power-button.png" alt="Logout" class="logout-icon">
      </a>
    </div>
  </header>

  <div class="dashboard">
    <nav class="sidebar" id="mobile-sidebar">
      <h3>NAVIGATION</h3>
      <ul>
        <li><a href="dashboard.php">Home</a></li>

        <?php if (in_array($role, ['root_admin', 'admin', 'user'])): ?>
          <li><a href="wifi.php">WiFi Network</a></li>
        <?php endif; ?>

        <?php if (in_array($role, ['root_admin', 'admin'])): ?>
          <li><a href="ethernet.php">Ethernet</a></li>
        <?php endif; ?>

        <?php if ($role === 'root_admin'): ?>
          <li><a href="firmware_update.php">Advanced</a></li>
        <?php endif; ?>

        <?php if (in_array($role, ['root_admin', 'admin'])): ?>
          <li><a href="security-firewall.php">Security & Firewall</a></li>
        <?php endif; ?>

        <?php if (in_array($role, ['root_admin', 'admin'])): ?>
          <li><a href="logs-diagnostics.php">Logs & Diagnostics</a></li>
        <?php endif; ?>

        <li class="active"><a href="starlink.php">STARLINK</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <h1>STARLINK DIAGNOSTICS</h1>
      <div id="json-output"></div>
    </main>
  </div>

  <script>
    document.getElementById('menu-toggle').addEventListener('click', () => {
      const sidebar = document.getElementById('mobile-sidebar');
      sidebar.classList.toggle('show-sidebar');
    });

    function refreshStarlink() {
      fetch("ajax_starlink.php")
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById("json-output");
          if (!container || typeof data !== 'object') return;

          container.innerHTML = "";
          const grid = document.createElement("div");
          grid.className = "starlink-grid";

          for (const key in data) {
            const val = typeof data[key] === 'object' ? JSON.stringify(data[key]) : data[key];

            const card = document.createElement("div");
            card.className = "diagnostic-card";
            card.innerHTML = `<h4>${key}</h4><p>${val}</p>`;
            grid.appendChild(card);
          }

          container.appendChild(grid);
        })
        .catch(err => console.error("Error loading Starlink data:", err));
    }

    setInterval(refreshStarlink, 10000);
    refreshStarlink();
  </script>
</body>
</html>