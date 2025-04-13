$(document).ready(function() {
    // Toggle sidebar on mobile
    $(".toggle-sidebar").click(function() {
        $(".sidebar").toggleClass("collapsed");
        $(".main-content").toggleClass("expanded");
    });
    
    // Section navigation
    $(".sidebar-menu li").click(function() {
        // Remove active class from all menu items
        $(".sidebar-menu li").removeClass("active");
        
        // Add active class to clicked menu item
        $(this).addClass("active");
        
        // Get section name
        const section = $(this).data("section");
        
        // Hide all sections
        $(".admin-section").removeClass("active");
        
        // If dashboard, just show it (it's already loaded)
        if (section === "dashboard") {
            $("#dashboard-section").addClass("active");
            return;
        }
        
        // Show loading in the section
        $(`#${section}-section`).html('<div class="loading">Loading...</div>').addClass("active");
        
        // Load section content via AJAX
        loadSection(section);
    });
    
    // View all links in dashboard
    $(".view-all").click(function(e) {
        e.preventDefault();
        
        // Get section name
        const section = $(this).data("section");
        
        // Trigger click on the corresponding sidebar menu item
        $(`.sidebar-menu li[data-section="${section}"]`).click();
    });
});

// Function to load section content
function loadSection(section) {
    $.ajax({
        url: `admin_sections/${section}.php`,
        success: function(response) {
            $(`#${section}-section`).html(response);
        },
        error: function() {
            $(`#${section}-section`).html('<div class="error">Failed to load section content.</div>');
        }
    });
}