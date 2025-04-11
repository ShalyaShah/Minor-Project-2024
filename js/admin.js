// admin.js
document.addEventListener('DOMContentLoaded', function() {
    // Navigation functionality
    const navLinks = document.querySelectorAll('.nav-links li a');
    const contentContainer = document.getElementById('content-container');
    const dashboardSection = document.getElementById('dashboard');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') !== 'logout.php') {
                e.preventDefault();
                
                // Remove active class from all links
                navLinks.forEach(navLink => {
                    navLink.parentElement.classList.remove('active');
                });
                
                // Add active class to clicked link
                this.parentElement.classList.add('active');
                
                // Get the section ID from href
                const sectionId = this.getAttribute('href').substring(1);
                
                // If it's dashboard, show the dashboard section and hide content container
                if (sectionId === 'dashboard') {
                    dashboardSection.style.display = 'block';
                    contentContainer.style.display = 'none';
                } else {
                    // Hide dashboard section and show content container
                    dashboardSection.style.display = 'none';
                    contentContainer.style.display = 'block';
                    
                    // Load the content for the selected section
                    loadSectionContent(sectionId);
                }
            }
        });
    });
    
    // Function to load section content via AJAX
    function loadSectionContent(sectionId) {
        // Show loading indicator
        contentContainer.innerHTML = '<div class="loading">Loading...</div>';
        
        // Fetch the content for the selected section
        fetch(`admin_sections/${sectionId}.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                contentContainer.innerHTML = html;
                initSectionFunctionality(sectionId);
            })
            .catch(error => {
                contentContainer.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Failed to load content. Please try again later.</p>
                        <button class="retry-btn" onclick="loadSectionContent('${sectionId}')">Retry</button>
                    </div>
                `;
                console.error('Error loading section content:', error);
            });
    }
    
    // Function to initialize section-specific functionality
    function initSectionFunctionality(sectionId) {
        switch(sectionId) {
            case 'users':
                // Initialize users section functionality
                console.log('Users section initialized');
                break;
            case 'bookings':
                // Initialize bookings section functionality
                console.log('Bookings section initialized');
                break;
            case 'passengers':
                // Initialize passengers section functionality
                console.log('Passengers section initialized');
                break;
            case 'reports':
                // Initialize reports section functionality
                console.log('Reports section initialized');
                break;
            case 'settings':
                // Initialize settings section functionality
                console.log('Settings section initialized');
                break;
        }
    }
});