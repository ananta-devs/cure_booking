<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #ffffff;
            padding: 20px;
        }

        .login-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            position: relative;
        }

        .header {
            background: #f9f9f9;
            padding: 30px 0 20px;
            text-align: center;
        }

        .header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            color: #777;
            font-size: 14px;
        }

        .form-container {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #9d50bb;
            outline: none;
        }

        .btn {
            background: linear-gradient(to right, #6e48aa, #9d50bb);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #loginMessage {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            display: none;
        }

        #loginMessage.error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
            display: block;
        }

        #loginMessage.success {
            background-color: #efe;
            color: #363;
            border: 1px solid #cfc;
            display: block;
        }

        /* Mobile-specific adjustments */
        @media (max-width: 480px) {
            .login-container {
                border-radius: 5px;
            }
            
            .header {
                padding: 20px 0 15px;
            }

            .header h1 {
                font-size: 22px;
            }

            .form-container {
                padding: 20px;
            }

            .form-group input {
                padding: 10px 12px;
                font-size: 15px;
            }

            .btn {
                padding: 10px;
                font-size: 15px;
            }
        }

        /* For very small screens */
        @media (max-width: 320px) {
            body {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 20px;
            }

            .form-container {
                padding: 15px;
            }
        }

        /* For larger screens - add some subtle improvements */
        @media (min-width: 768px) {
            .login-container {
                transition: transform 0.3s;
            }

            .login-container:hover {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        <div class="header">
            <h1>Welcome Back</h1>
            <p>Please login to your account</p>
        </div>
        <div class="form-container">
            <form id="login-form" action="log.php" method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn" id="loginBtn">Login</button>
                <div id="loginMessage"></div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageElement = document.getElementById('loginMessage');
            const loginBtn = document.getElementById('loginBtn');
            
            // Disable button and show loading state
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';
            messageElement.style.display = 'none';
            messageElement.className = '';
            
            // Create form data
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            
            // Send login request - use relative path
            fetch('log.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    messageElement.textContent = data.message;
                    messageElement.className = 'success';
                    messageElement.style.display = 'block';
                    
                    // Redirect to profile page after a short delay
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    // Display error message
                    messageElement.textContent = data.message;
                    messageElement.className = 'error';
                    messageElement.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageElement.textContent = 'Connection error. Please check your network and try again.';
                messageElement.className = 'error';
                messageElement.style.display = 'block';
            })
            .finally(() => {
                // Re-enable button
                loginBtn.disabled = false;
                loginBtn.textContent = 'Login';
            });
        });
    </script>

</body>
</html>