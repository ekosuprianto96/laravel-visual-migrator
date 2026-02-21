<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Visual Migrator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Visual Migrator Assets (Published via Service Provider) -->
    <link rel="stylesheet" href="{{ asset('vendor/visual-migrator/assets/index.css') }}">
    
    <style>
        body, html { margin: 0; padding: 0; height: 100%; width: 100%; overflow: hidden; background: #1a1a1a; }
        #app { height: 100vh; width: 100vw; }
    </style>
</head>
<body>
    <div id="app"></div>

    <!-- The Core UI Script -->
    <script src="{{ asset('vendor/visual-migrator/assets/index.js') }}"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.MongoDiagram) {
                window.MongoDiagram.init({
                    // Point to the routes defined in the package
                    baseUrl: "{{ url(config('visual-migrator.path')) }}",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    autoFetch: true
                });
            } else {
                console.error('Mongo Diagram Core UI not found. Make sure to publish assets or run the dev server.');
                
                // Fallback for development if you're running the Vue app on a different port
                if (confirm('Mongo Diagram Core UI not found in assets. Would you like to use the local dev server (http://localhost:5173)?')) {
                    const script = document.createElement('script');
                    script.src = 'http://localhost:5173/src/main.js';
                    script.type = 'module';
                    document.head.appendChild(script);
                }
            }
        });
    </script>
</body>
</html>
