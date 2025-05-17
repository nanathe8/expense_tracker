import 'package:flutter/material.dart';
import 'sideBar.dart';
// import 'addExpenseScreen.dart';

class BaseScreen extends StatelessWidget {
  final Widget body;
  final String title;

  const BaseScreen({super.key, required this.body, required this.title});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        // title: const Text('Expense Tracker'),
        backgroundColor: Colors.deepPurple,
      ),
      drawer: const Sidebar(),
      // backgroundColor: Colors.black,
      body: SafeArea(child: body),
      // body: body,

      // Central Floating Action Button
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.pushNamed(context, '/AddTransactionScreen'); // Your 'Add' screen route
        },
        backgroundColor: Colors.deepPurple,
        shape: const CircleBorder(),
        child: const Icon(Icons.add, color: Colors.white),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,

      // Custom BottomAppBar
      bottomNavigationBar: BottomAppBar(
        shape: const CircularNotchedRectangle(),
        notchMargin: 8,
        color: const Color(0xFF2C2C35), // dark grey like in your screenshot
        child: SizedBox(
          height: 60,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: <Widget>[
              IconButton(
                icon: const Icon(Icons.home),
                color: Colors.white,
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/home_screen.dart');
                },
              ),
              IconButton(
                icon: const Icon(Icons.bar_chart),
                color: Colors.white,
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/statistics');
                },
              ),
              const SizedBox(width: 40), // space for the center FAB
              IconButton(
                icon: const Icon(Icons.calendar_today),
                color: Colors.white,
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/calendar');
                },
              ),
              IconButton(
                icon: const Icon(Icons.account_circle),
                color: Colors.white,
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/profile');
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
