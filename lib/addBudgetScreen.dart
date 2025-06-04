import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'config.dart';

class BudgetScreen extends StatefulWidget {
  const BudgetScreen({super.key});

  @override
  _BudgetScreenState createState() => _BudgetScreenState();
}

class _BudgetScreenState extends State<BudgetScreen> {
  // final TextEditingController _budgetAmountController = TextEditingController();
  final TextEditingController _budgetNameController = TextEditingController();  // Declare the controller for budgetName
  DateTime? _startDate;
  DateTime? _endDate;
  bool isLoading = false;
  int userId = 0; // Default userId to 0 if not found
  int? groupID; // Group ID can be null (for personal budget)
  String username = 'Loading...'; // To track username

  // Function to save the session ID
  Future<void> saveSessionId(String sessionId) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setString('session_id', sessionId);
  }

  // Load userId from SharedPreferences
  Future<void> _loadUserId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    int storedUserId = prefs.getInt('userId') ?? 0; // Default to 0 if userId is not found

    print('Loaded userId: $storedUserId');  // Debugging output to check if userId is properly loaded

    if (storedUserId != 0) {
      setState(() {
        userId = storedUserId;  // Set the userId from SharedPreferences
      });
    } else {
      setState(() {
        username = 'No user found';
      });
    }
  }

  // Function to select the start date
  Future<void> _selectStartDate(BuildContext context) async {
    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null && pickedDate != _startDate) {
      setState(() {
        _startDate = pickedDate;
      });
    }
  }

  // Function to select the end date
  Future<void> _selectEndDate(BuildContext context) async {
    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null && pickedDate != _endDate) {
      setState(() {
        _endDate = pickedDate;
      });
    }
  }

  // Function to save the budget (Creating Budget)
  Future<void> _saveBudget() async {
    // String budgetAmount = _budgetAmountController.text;
    String budgetName = _budgetNameController.text;  // Added for budget name

    // Validate the input fields
    if (budgetName.isEmpty || _startDate == null || _endDate == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text("Please fill in all fields."),
      ));
      return;
    }

    // // Validate if budgetAmount is a valid number
    // if (double.tryParse(budgetAmount) == null) {
    //   ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
    //     content: Text("Please enter a valid budget amount."),
    //   ));
    //   return;
    // }

    setState(() {
      isLoading = true;  // Show loading spinner
    });

    String url = '${CONFIG.SERVER}/createBudget.php'; // PHP backend URL
    try {
      // Retrieve the session ID
      String? sessionId = await getSessionId();
      print('Session ID retrieved: $sessionId'); // Debugging: Log the session ID

      if (sessionId == null) {
        // Handle case where session ID is not found
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text("Session expired. Please log in again."),
        ));
        return;
      }

      final response = await http.post(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          'Cookie': 'PHPSESSID=$sessionId',  // Pass the session ID in the Cookie header
        },
        body: json.encode({
          // 'budgetAmount': budgetAmount,
          'budgetName': budgetName,  // Send budgetName to the backend
          'startDate': _startDate?.toIso8601String(),
          'endDate': _endDate?.toIso8601String(),
          'groupID': groupID,  // If groupID is null, it will be passed as NULL for personal budget
          'userID': userId,  // Send userId as part of the body
        }),
      );

      setState(() {
        isLoading = false;  // Hide loading spinner after response
      });

      if (response.statusCode == 200) {
        print('Response body: ${response.body}');  // Log raw response body
        try {
          var data = json.decode(response.body);
          if (data['status'] == 'success') {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(
              content: Text(data['message']),
            ));
          } else {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(
              content: Text(data['message']),
            ));
          }
        } catch (e) {
          print('Error decoding JSON: $e');  // Log any decoding error
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text("Error decoding response."),
          ));
        }
      } else {
        throw Exception('Failed to save budget');
      }
    } catch (e) {
      setState(() {
        isLoading = false;  // Hide loading spinner if error occurs
      });
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text("Error: $e"),
      ));
    }
  }

  // Renamed the method to retrieveSessionId to avoid conflict
  Future<String?> getSessionId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? sessionId = prefs.getString('session_id');  // Retrieve the stored session ID

    print('Session ID retrieved: $sessionId');  // Log the retrieved session ID

    return sessionId;
  }

  @override
  void initState() {
    super.initState();
    _loadUserId();  // Load userId when the BudgetScreen is created
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Create or Update Budget'),
        backgroundColor: const Color(0xFF6D44B8),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Enter your budget details',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _budgetNameController,  // Added controller for budgetName
              decoration: const InputDecoration(
                labelText: 'Budget Name',
                border: OutlineInputBorder(),
              ),
            ),

            // const SizedBox(height: 16),
            // TextField(
            //   controller: _budgetAmountController,
            //   decoration: const InputDecoration(
            //     labelText: 'Budget Amount',
            //     border: OutlineInputBorder(),
            //   ),
            //   keyboardType: TextInputType.number,
            // ),

            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () => _selectStartDate(context),
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Start Date',
                        border: OutlineInputBorder(),
                      ),
                      child: Text(
                        _startDate == null
                            ? 'Select Start Date'
                            : _startDate!.toLocal().toString().split(' ')[0],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: InkWell(
                    onTap: () => _selectEndDate(context),
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'End Date',
                        border: OutlineInputBorder(),
                      ),
                      child: Text(
                        _endDate == null
                            ? 'Select End Date'
                            : _endDate!.toLocal().toString().split(' ')[0],
                      ),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: isLoading ? null : _saveBudget,  // Disable button while loading
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6D44B8),
              ),
              child: isLoading
                  ? const CircularProgressIndicator(color: Colors.white)
                  : const Text('Save Budget'),
            ),
          ],
        ),
      ),
    );
  }
}
