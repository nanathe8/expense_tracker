import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'home_screen.dart'; // Import HomeScreen here
import 'register.dart'; // Import RegisterScreen here
import 'config.dart'; // Import config for backend URL

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool isLoading = false;  // To track the loading state

// Save the session ID after login
  Future<void> saveSessionId(String sessionId) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setString('session_id', sessionId);  // Store the session ID
    // Debugging: Log the session ID to confirm it's saved
    print('Session ID saved: $sessionId');
  }

  // Save the userID after successful login
  Future<void> saveUserId(int userId) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setInt('userId', userId);  // Store the userID
    print('UserID saved: $userId');  // Debugging: Log the userID to confirm it's saved
  }

  Future<void> _login() async {
    String email = _emailController.text;
    String password = _passwordController.text;

    // Basic form validation
    if (email.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text("Please fill in both fields."),
      ));
      return;
    }

    String url = '${CONFIG.SERVER}/login.php'; // PHP login script URL

    setState(() {
      isLoading = true; // Set loading state to true
    });

    try {
      final response = await http.post(
        Uri.parse(url),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'email': email, 'password': password}),
      );

      print('HTTP Status: ${response.statusCode}');
      print('Response Body: ${response.body}');

      if (!mounted) return;

      setState(() {
        isLoading = false; // Set loading state to false after response
      });

      if (response.statusCode == 200) {
        var data = json.decode(response.body);

        if (data['status'] == 'success') {
          // After successful login, store the session ID and userId
          String sessionId = data['session_id'];
          int userId = data['user_id'];  // Assuming userId is in the response

          // Save sessionId and userId in SharedPreferences
          await saveSessionId(sessionId);  // Save session ID
          await saveUserId(userId);        // Save userId

          final cookies = response.headers['set-cookie'];
          if (cookies != null) {
            final sessionId = RegExp(r'PHPSESSID=([^;]+)').firstMatch(cookies)?.group(1);
            if (sessionId != null) {
              SharedPreferences prefs = await SharedPreferences.getInstance();
              await prefs.setString('PHPSESSID', sessionId);
            }
          }

          // Navigate to the HomeScreen
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => const HomeScreen()),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: const Text("Login failed. Please try again."),
          ));
        }
      } else {
        throw Exception('Failed to login');
      }
    } catch (e) {
      if (!mounted) return;

      setState(() {
        isLoading = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text("Error: $e"),
      ));
    }
  }

  // // Save the userID after successful login
  // Future<void> saveUserId(int userId) async {
  //   SharedPreferences prefs = await SharedPreferences.getInstance();
  //   await prefs.setInt('userId', userId);  // Store the userID
  //   print('UserID saved: $userId');  // Debugging: Log the userID to confirm it's saved
  // }
  //
  //
  // Future<void> _login() async {
  //   String email = _emailController.text;
  //   String password = _passwordController.text;
  //
  //   // Basic form validation
  //   if (email.isEmpty || password.isEmpty) {
  //     ScaffoldMessenger.of(context).showSnackBar(SnackBar(
  //       content: const Text("Please fill in both fields."),
  //     ));
  //     return;
  //   }
  //
  //   String url = '${CONFIG.SERVER}/login.php'; // PHP login script URL
  //
  //   setState(() {
  //     isLoading = true; // Set loading state to true
  //   });
  //
  //   try {
  //     final response = await http.post(
  //       Uri.parse(url),
  //       headers: {'Content-Type': 'application/json'},
  //       body: json.encode({'email': email, 'password': password}),
  //     );
  //
  //     print('HTTP Status: ${response.statusCode}');
  //     print('Response Body: ${response.body}');
  //
  //     if (!mounted) return;
  //
  //     setState(() {
  //       isLoading = false; // Set loading state to false after response
  //     });
  //
  //     if (response.statusCode == 200) {
  //       var data = json.decode(response.body);
  //
  //       if (data['status'] == 'success') {
  //         // After successful login, store the session ID
  //         String sessionId = data['session_id'];
  //         await saveSessionId(sessionId);  // Save session ID in SharedPreferences
  //
  //         // Navigate to the HomeScreen
  //         Navigator.pushReplacement(
  //           context,
  //           MaterialPageRoute(builder: (context) => const HomeScreen()),
  //         );
  //       } else {
  //         ScaffoldMessenger.of(context).showSnackBar(SnackBar(
  //           content: const Text("Login failed. Please try again."),
  //         ));
  //       }
  //     } else {
  //       throw Exception('Failed to login');
  //     }
  //   } catch (e) {
  //     if (!mounted) return;
  //
  //     setState(() {
  //       isLoading = false;
  //     });
  //
  //     ScaffoldMessenger.of(context).showSnackBar(SnackBar(
  //       content: Text("Error: $e"),
  //     ));
  //   }
  // }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F0C29),
      body: Center(
        child: Container(
          width: 350,
          padding: const EdgeInsets.all(30),
          decoration: BoxDecoration(
            color: const Color.fromRGBO(255, 255, 255, 0.1),
            borderRadius: BorderRadius.circular(10),
            boxShadow: const [
              BoxShadow(color: Colors.black26, spreadRadius: 1, blurRadius: 10),
            ],
          ),
          child: Form(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: <Widget>[
                const Text(
                  'Login to Your Account',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    hintText: 'Enter your email',
                    hintStyle: const TextStyle(color: Colors.white70),
                    filled: true,
                    fillColor: const Color.fromRGBO(255, 255, 255, 0.1),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(5),
                      borderSide: BorderSide.none,
                    ),
                  ),
                  style: const TextStyle(color: Colors.white),
                ),
                const SizedBox(height: 15),
                TextFormField(
                  controller: _passwordController,
                  obscureText: true,
                  decoration: InputDecoration(
                    hintText: 'Enter your password',
                    hintStyle: const TextStyle(color: Colors.white70),
                    filled: true,
                    fillColor: const Color.fromRGBO(255, 255, 255, 0.1),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(5),
                      borderSide: BorderSide.none,
                    ),
                  ),
                  style: const TextStyle(color: Colors.white),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: isLoading ? null : _login,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF6D44B8),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(5),
                    ),
                  ),
                  child: isLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text(
                    'Login',
                    style: TextStyle(
                      fontSize: 15,
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(height: 15),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text(
                      "Don't have an account? ",
                      style: TextStyle(color: Colors.white),
                    ),
                    GestureDetector(
                      onTap: () {
                        Navigator.pushReplacement(
                          context,
                          MaterialPageRoute(builder: (context) => const RegisterScreen()),
                        );
                      },
                      child: const Text(
                        "Register",
                        style: TextStyle(
                          color: Colors.purple,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
