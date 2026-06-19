<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SpendTracker')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Modal theme (shared across all pages) -->
    <link href="{{ asset('css/modals.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body>
    <div id="app">
        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>

<script>
    // Prevent back button after logout
    (function() {
        // Push a new state on page load
        window.history.pushState(null, null, window.location.href);

        // Handle popstate event (back button)
        window.addEventListener('popstate', function(event) {
            // Check if user is authenticated by checking if session exists
            // If not, redirect to login
            fetch('/api/user', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                            '',
                    },
                })
                .then(response => {
                    if (response.status === 401) {
                        // User is not authenticated, redirect to login
                        window.location.replace('/login');
                    } else {
                        // User is authenticated, allow back navigation
                        window.history.back();
                    }
                })
                .catch(() => {
                    // On error, assume not authenticated
                    window.location.replace('/login');
                });
        });
    })();
</script>

</html>
