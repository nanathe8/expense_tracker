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
    _loadUserId();
  }

  Future<void> _loadUserId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    int storedUserId = prefs.getInt('userId') ?? 0;

    print('Loaded userId: $storedUserId');
    print('Stored userId: ${prefs.getInt('userId')}');

    if (storedUserId != 0) {
      setState(() {
        userId = storedUserId;
      });
      fetchUserData(userId);
    } else {
      setState(() {
        username = 'No user found';
      });
    }
  }

  Future<void> fetchUserData(int userId) async {
    print('Fetching data for userId: $userId');

    final response = await http.get(Uri.parse('${CONFIG.SERVER}/getUsername.php?userID=$userId'));

    if (response.statusCode == 200) {
      print('Response: ${response.body}');

      final data = json.decode(response.body);

      if (data.containsKey('error')) {
        setState(() {
          username = 'Guest';
        });
      } else {
        setState(() {
          username = data['name'];
        });
      }
    } else {
      print('Error: ${response.statusCode}');
      setState(() {
        username = 'Error loading data';
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
                Icon(
                  Icons.account_circle,
                  size: 60,
                  color: Colors.white,
                ),
                const SizedBox(height: 10),
                Text(
                  username,
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
          // <-- New Group Expense navigation ListTile -->
          ListTile(
            leading: const Icon(Icons.group, color: Colors.white),
            title: const Text('Group Expense', style: TextStyle(color: Colors.white)),
            onTap: () {
              Navigator.pushReplacementNamed(context, '/groupDashboardScreen');
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
              await prefs.remove('session_id');
              await prefs.remove('userId');
              await logout(context);
              Navigator.pushReplacementNamed(context, '/login_screen');
            },
          ),
        ],
      ),
    );
  }
}
