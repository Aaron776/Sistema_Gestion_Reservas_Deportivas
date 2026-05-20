<div class="top-nav" style="position: relative; z-index: 9999;">
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar reservas, canchas...">
    </div>
    <div class="user-profile">
        <div class="notification-badge" id="notificationBtn" style="position: relative;">
            <i class="far fa-bell" style="font-size: 1.3rem;"></i>
            <span class="badge-dot" id="notificationCount" style="display: none; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: bold; color: white; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 10px; box-sizing: border-box; right: -12px; top: -8px; background: #ef4444; border: 2px solid #0a141c;"></span>
            
            <!-- Dropdown de Notificaciones -->
            <div id="notificationDropdown" class="notification-dropdown" style="display: none; position: absolute; top: 100%; right: -10px; width: 320px; background: rgba(15, 25, 35, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 12px; margin-top: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 1000; overflow: hidden; cursor: default;">
                <div style="padding: 12px 16px; border-bottom: 1px solid rgba(72, 187, 120, 0.2); font-weight: 600; display: flex; justify-content: space-between; align-items: center; color: white;">
                    <span><i class="fas fa-bell" style="color: #22D3EE;"></i> Notificaciones</span>
                    <span id="unreadsText" style="font-size: 0.75rem; color: #a3e635; background: rgba(163, 230, 53, 0.2); padding: 2px 8px; border-radius: 10px;">0 nuevas</span>
                </div>
                <div id="notificationList" style="max-height: 320px; overflow-y: auto;">
                    <!-- Items dinámicos -->
                </div>
            </div>
        </div>
        <div class="avatar">
            <span><?php echo htmlspecialchars(ucfirst(substr($_SESSION['nombre'], 0, 2))); ?></span>
        </div>
        <div style="font-weight: 500;"><?php echo htmlspecialchars(ucfirst($_SESSION['nombre'])); ?> <span style="font-size:0.7rem; color:#7e9eb5;"><?php echo htmlspecialchars(ucfirst($_SESSION['rol'])); ?></span></div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const notifBtn = document.getElementById("notificationBtn");
    const notifDropdown = document.getElementById("notificationDropdown");
    const notifCount = document.getElementById("notificationCount");
    const notifList = document.getElementById("notificationList");
    const unreadsText = document.getElementById("unreadsText");
    let isDropdownOpen = false;

    function loadNotifications() {
        fetch('<?= $base_url ?>controladores/notificaciones/obtener.php')
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.unleidas > 0) {
                    notifCount.style.display = 'flex';
                    notifCount.innerText = data.unleidas;
                    unreadsText.innerText = data.unleidas + ' nuevas';
                } else {
                    notifCount.style.display = 'none';
                    unreadsText.innerText = '0 nuevas';
                }
                
                notifList.innerHTML = '';
                if(data.listado.length === 0) {
                    notifList.innerHTML = '<div style="padding: 20px; text-align: center; color: #7e9eb5; font-size: 0.85rem;">No tienes notificaciones recientes.</div>';
                } else {
                    data.listado.forEach(n => {
                        let icon = 'fa-info-circle';
                        let color = '#22D3EE';
                        if(n.tipo === 'reserva') { icon = 'fa-calendar-alt'; color = '#a3e635'; }
                        else if(n.tipo === 'pago') { icon = 'fa-money-bill-wave'; color = '#f59e0b'; }
                        else if(n.tipo === 'sistema') { icon = 'fa-cog'; color = '#2dd4bf'; }
                        
                        let opacity = n.leida === 'si' ? '0.6' : '1';
                        let title = n.titulo.charAt(0).toUpperCase() + n.titulo.slice(1);
                        
                        notifList.innerHTML += `
                            <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; gap: 12px; align-items: start; opacity: ${opacity}; transition: 0.2s;">
                                <div style="background: rgba(34, 211, 238, 0.1); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                                    <i class="fas ${icon}" style="color: ${color}; font-size: 0.85rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.85rem; font-weight: 600; color: #eef5ff; margin-bottom: 2px;">Notificación de ${title}</div>
                                    <div style="font-size: 0.75rem; color: #cbd5e6; line-height: 1.3;">${n.mensaje}</div>
                                    <div style="font-size: 0.65rem; color: #7e9eb5; margin-top: 4px;"><i class="far fa-clock"></i> ${n.fecha}</div>
                                </div>
                            </div>
                        `;
                    });
                }
            }
        })
        .catch(err => console.error("Error fetching notifications:", err));
    }

    notifBtn.addEventListener("click", function(e) {
        if(e.target.closest('#notificationDropdown')) return; 
        isDropdownOpen = !isDropdownOpen;
        notifDropdown.style.display = isDropdownOpen ? 'block' : 'none';
        
        if(isDropdownOpen && notifCount.style.display !== 'none') {
            fetch('<?= $base_url ?>controladores/notificaciones/marcar_leidas.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    notifCount.style.display = 'none';
                    unreadsText.innerText = '0 nuevas';
                }
            });
        }
    });

    document.addEventListener("click", function(e) {
        if(isDropdownOpen && !notifBtn.contains(e.target)) {
            isDropdownOpen = false;
            notifDropdown.style.display = 'none';
            loadNotifications(); // Recargar al cerrar para que se reflejen como "leídas" opacas
        }
    });

    loadNotifications();
    setInterval(loadNotifications, 30000); // Check every 30s
});
</script>