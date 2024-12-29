// Modified radar initialization
document.addEventListener('DOMContentLoaded', function() {
    fetch('https://softwareveteran.dev/api/radar.php')
        .then(response => response.json())
        .then(config => {
            // Add any default configuration values
            config.print_layout = true;
            config.links_in_new_tabs = true;
            
            // Initialize the radar with the data from the database
            radar_visualization(config);
        })
        .catch(error => {
            console.error('Error loading radar data:', error);
            // Display error message on the page
            document.getElementById('radar').innerHTML = 
                '<text x="50%" y="50%" text-anchor="middle">Error loading radar data</text>';
        });
});


