<?php

/**
 * Helper to include Vite assets in a PHP project.
 * 
 * @param string|array $entries Entry point(s) e.g. 'backend/js/main.js' or ['backend/js/main.js', 'frontend/css/styles.css']
 * @return string HTML tags for the assets
 */
function vite($entries) {
    if (is_string($entries)) {
        $entries = [$entries];
    }

    // Detect base path relative to document root
    // We know this file is at [ROOT]/backend/vite_helper.php, so dirname(__DIR__) is [ROOT]
    $project_root = str_replace('\\', '/', dirname(__DIR__));
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $base_url = str_replace($doc_root, '', $project_root);

    $is_dev = false;
    // Check if Vite dev server is running
    $fp = @fsockopen('localhost', 5173, $errno, $errstr, 0.05);
    if ($fp) {
        $is_dev = true;
        fclose($fp);
    }

    if ($is_dev) {
        $html = '<script type="module" src="http://localhost:5173/@vite/client"></script>';
        foreach ($entries as $entry) {
            $url = 'http://localhost:5173/' . ltrim($entry, '/');
            if (str_ends_with($entry, '.css')) {
                $html .= '<link rel="stylesheet" href="' . $url . '">';
            } else {
                $html .= '<script type="module" src="' . $url . '"></script>';
            }
        }
        return $html;
    }

    // Production mode
    $manifestPath = __DIR__ . '/../dist/.vite/manifest.json';
    if (!file_exists($manifestPath)) {
        return "<!-- Vite manifest not found. Run 'npm run build' -->";
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);
    $html = '';
    
    foreach ($entries as $entry) {
        $entry = ltrim($entry, '/');
        if (!isset($manifest[$entry])) {
            $html .= "<!-- Asset $entry not found in manifest -->";
            continue;
        }

        $asset = $manifest[$entry];
        
        // Include the main file
        $fileUrl = $base_url . '/dist/' . $asset['file'];
        if (str_ends_with($fileUrl, '.css')) {
            $html .= '<link rel="stylesheet" href="' . $fileUrl . '">';
        } else {
            $html .= '<script type="module" src="' . $fileUrl . '"></script>';
        }

        // Include any CSS associated with JS files
        if (isset($asset['css'])) {
            foreach ($asset['css'] as $cssFile) {
                $html .= '<link rel="stylesheet" href="' . $base_url . '/dist/' . $cssFile . '">';
            }
        }
    }

    return $html;
}
