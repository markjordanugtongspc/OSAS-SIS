import Swal from 'sweetalert2';

/**
 * Shows a loading alert during login process
 */
export function showLoginLoading() {
    Swal.fire({
        title: 'Logging in...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Shows an error alert for failed login
 * @param {string} message - Error message to display (optional)
 */
export function showLoginError(message = 'Invalid username or password. Please try again.') {
    Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: message,
        confirmButtonColor: '#800000'
    });
}

/**
 * Handles the login form submission
 * @param {FormData} formData - Form data containing username and password
 * @returns {Promise<Object>} Promise that resolves with login result
 */
export async function handleLogin(formData) {
    const username = formData.get('username');
    const password = formData.get('password');
    
    try {
        // TODO: Replace with actual login API call
        // const response = await fetch('/api/login', {
        //     method: 'POST',
        //     body: formData
        // });
        // 
        // if (!response.ok) {
        //     throw new Error('Login failed');
        // }
        // 
        // const result = await response.json();
        // return { success: true, data: result };
        
        // For now, simulate failed login after 1 second
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Simulate error/invalid login (replace with actual API response)
        return { 
            success: false, 
            error: 'Invalid username or password. Please try again.' 
        };
        
    } catch (error) {
        return { 
            success: false, 
            error: error.message || 'An error occurred during login' 
        };
    }
}

/**
 * Processes the login form submission
 * @param {FormData} formData - Form data containing username and password
 */
export async function processLogin(formData) {
    // Show loading alert
    showLoginLoading();
    
    try {
        const username = formData.get('username');
        const password = formData.get('password');
        
        // Simulate API call delay
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // TEMPORARY: Check for admin credentials
        // TODO: Replace with actual API authentication
        if (username === 'admin' && password === 'admin') {
            // Successful login - redirect to dashboard
            window.location.href = '/frontend/dashboard.php';
            return;
        } else {
            // Invalid credentials
            showLoginError('Invalid username or password. Please try again.');
        }
        
    } catch (error) {
        // Show error alert
        showLoginError(error.message || 'An unexpected error occurred');
    }
}
