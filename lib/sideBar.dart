import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'config.dart';
import 'logout_screen.dart';

class Sidebar extends StatefulWidget {
  const Sidebar({super.key});

  @override
  _SidebarState createState() => _SidebarState();
}

class _SidebarState extends State<Sidebar> {
  String username = 'Loading...';
  int userId = 0;

  @override
  void initState() {
    super.initState();
    _loadUserId();  // Ensure this is called when Sidebar is initialized
  }

  // Load the userId from SharedPreferences
  Future<void> _loadUserId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    int storedUserId = prefs.getInt('userId') ?? 0;

    print('Loaded userId: $storedUserId');  // Debugging output

    if (storedUserId != 0) {
      setState(() {
        userId = storedUserId;  // Set the userId from SharedPreferences
      });
      fetchUserData(userId);  // Fetch user data after setting userId
    } else {
      setState(() {
        username = 'No user found';
      });
    }
  }

  // Fetch user data from the backend using the userId
  Future<void> fetchUserData(int userId) async {
    print('Fetching data for userId: $userId');  // Debugging output

    final response = await http.get(Uri.parse('${CONFIG.SERVER}/getUsername.php?userID=$userId'));

    if (response.statusCode == 200) {
      print('Response: ${response.body}');  // Log the response body

      final data = json.decode(response.body);

      if (data.containsKey('error')) {
        setState(() {
          username = 'Guest';  // Show 'Guest' if user data can't be fetched
        });
      } else {
        setState(() {
          username = data['name'];  // Set the username
        });
      }
    } else {
      print('Error: ${response.statusCode}');
      setState(() {
        username = 'Error loading data';  // Show error message if API fails
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: const Color(0xFF1E1E2E),
      child: ListView(
        children: [
          DrawerHeader(
            decoration: BoxDecoration(
              color: const Color(0xFF2A2A3A),
            ),
            child: Column(
              children: [
                // Instead of a profile picture, display an icon
                Icon(
                  Icons.account_circle,  // Default icon for user
                  size: 60,
                  color: Colors.white,
                ),
                const SizedBox(height: 10),
                Text(
                  username,  // Display the username or 'Guest' if no user is found
                  style: const TextStyle(color: Colors.white, fontSize: 20),
                ),
              ],
            ),
          ),
          ListTile(
            leading: const Icon(Icons.home, color: Colors.white),
            title: const Text('Home', style: TextStyle(color: Colors.white)),
            onTap: () {
              Navigator.pushReplacementNamed(context, '/home_screen');
            },
          ),
          ListTile(
            leading: const Icon(Icons.settings, color: Colors.white),
            title: const Text('Settings', style: TextStyle(color: Colors.white)),
            onTap: () {
              Navigator.pop(context);
            },
          ),
          ListTile(
            leading: const Icon(Icons.exit_to_app, color: Colors.white),
            title: const Text('Logout', style: TextStyle(color: Colors.white)),
            onTap: () async {
              SharedPreferences prefs = await SharedPreferences.getInstance();
              await prefs.remove('session_id');  // Remove session ID
              await prefs.remove('userId');  // Remove user ID

              // Navigate to the Login screen
              Navigator.pushReplacementNamed(context, '/login_screen');  // Make sure '/login_screen' is the route to your login screen
            },
          ),
        ],
      ),
    );
  }
}
