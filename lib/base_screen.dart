import 'package:flutter/material.dart';
import 'sideBar.dart';

class BaseScreen extends StatelessWidget {
  final Widget body;
  final String title;
  final List<Widget>? actions; // Add this

  const BaseScreen({super.key, required this.body, required this.title, this.actions,});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        actions: actions,
        backgroundColor: Colors.deepPurple,
      ),
      drawer: const Sidebar(),
      body: SafeArea(child: body),

      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.pushNamed(context, '/AddTransactionScreen');
        },
        backgroundColor: Colors.deepPurple,
        shape: const CircleBorder(),
        child: const Icon(Icons.add, color: Colors.white),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,

      bottomNavigationBar: BottomAppBar(
        shape: const CircularNotchedRectangle(),
        notchMargin: 8,
        color: const Color(0xFF2C2C35),
        child: SizedBox(
          height: 60,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: <Widget>[
              IconButton(
                icon: const Icon(Icons.home),
                color: Colors.white,
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/home_screen');
                },
              ),
              IconButton(
                icon: const Icon(Icons.bar_chart),
                color: Colors.white,
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/StatisticsScreen');
                },
              ),
              const SizedBox(width: 40),
              IconButton(
                icon: const Icon(Icons.calendar_today),
                color: Colors.white,
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/budgetScreen');
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
