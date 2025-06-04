import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'login_screen.dart'; // Import LoginScreen here
import 'package:shared_preferences/shared_preferences.dart'; // Use shared preferences for storing user data
import 'config.dart';

Future<void> logout(BuildContext context) async {
  print("Logout function triggered");

  // URL to the PHP logout script
  final url = '${CONFIG.SERVER}/logout.php'; // Adjust with your actual URL

  try {
    final response = await http.get(Uri.parse(url));

    print("HTTP Status: ${response.statusCode}");
    print("Response Body: ${response.body}");

    if (response.statusCode == 200) {
      var data = json.decode(response.body);

      if (data['status'] == 'success') {
        // Clear the stored user data from shared preferences
        final prefs = await SharedPreferences.getInstance();
        await prefs.clear(); // Clear all saved data (like user session info)

        // Navigate to the Login screen
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => const LoginScreen()),
        );
      } else {
        // If logout failed, show an error message
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Logout failed: ${data['message']}")),
        );
      }
    } else {
      throw Exception('Failed to logout');
    }
  } catch (e) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text("Error: $e")),
    );
  }
}
