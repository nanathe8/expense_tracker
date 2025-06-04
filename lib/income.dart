import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert'; // For JSON parsing
import 'config.dart'; // To get the server URL

class IncomeScreen extends StatefulWidget {
  const IncomeScreen({Key? key}) : super(key: key);

  @override
  _IncomeScreenState createState() => _IncomeScreenState();
}

class _IncomeScreenState extends State<IncomeScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _amountController = TextEditingController();
  final TextEditingController _sourceController = TextEditingController();
  final TextEditingController _dateController = TextEditingController();
  List<dynamic> budgets = []; // List to hold budget options
  String? selectedBudget; // Variable to store the selected budget
  int userId = 0; // Default userId to 0 if not found

  // Function to select a date using a calendar
  Future<void> _selectDate(BuildContext context) async {
    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null && pickedDate != DateTime.now()) {
      setState(() {
        _dateController.text = pickedDate.toLocal().toString().split(' ')[0]; // Format date as 'yyyy-MM-dd'
      });
    }
  }

  Future<void> _loadUserId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    int? storedUserId = prefs.getInt('userId');

    if (storedUserId != null && storedUserId > 0) {
      setState(() {
        userId = storedUserId;
      });
      fetchBudgets(userId);  // Fetch budgets after userId is properly set
    }
  }

  Future<String?> getSessionId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? sessionId = prefs.getString('session_id');  // Retrieve session ID
    return sessionId;
  }

  Future<void> fetchBudgets(int userId) async {
    final url = '${CONFIG.SERVER}/getBudgetsByUser.php';  // Update to your endpoint
    try {
      final response = await http.post(Uri.parse(url), body: {
        'userID': userId.toString(),
      });

      print('Response body: ${response.body}');  // Debugging line

      if (response.statusCode == 200) {
        if (response.headers['content-type']?.contains('application/json') == true) {
          try {
            final data = json.decode(response.body);
            print('Decoded response: $data');  // Debugging line to see the decoded data
            if (data != null && data['budgets'] != null) {
              setState(() {
                budgets = List.from(data['budgets'].where((budget) => budget['groupID'] == null));
              });
            } else {
              setState(() {
                budgets = [];  // Empty list if no budgets found
              });
            }
          } catch (e) {
            print('Error parsing response: $e');  // Debugging line to catch any parsing errors
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error parsing response: $e")));
          }
        } else {
          print('Unexpected content-type: ${response.headers['content-type']}');
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Unexpected server response format")));
        }
      } else {
        print('Failed to load budgets. Status code: ${response.statusCode}');
        throw Exception('Failed to load budgets');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
    }
  }

  Future<void> _submitIncome() async {
    if (_formKey.currentState!.validate()) {
      final amount = _amountController.text;
      final source = _sourceController.text.trim(); // Use the exact text typed by the user
      final date = _dateController.text;

      if (source.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Please enter an income source")));
        return; // Stop submission if the source is empty
      }

      try {
        final sessionId = await getSessionId();
        print('Session ID: $sessionId');  // Debugging: Log session ID

        // Prepare the data to send to the PHP backend
        final response = await http.post(
          Uri.parse('${CONFIG.SERVER}/income.php'), // PHP backend URL
          headers: {
            'Content-Type': 'application/json',
            'Cookie': 'PHPSESSID=$sessionId',  // Send session ID as a cookie in the request headers
          },
          body: json.encode({
            'amount': amount,
            'source': source,
            'date': date,
          }),
        );

        // Print the response for debugging
        print('Response status: ${response.statusCode}');
        print('Response body: ${response.body}'); // Print raw response to see what is returned

        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          if (data['status'] == 'success') {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Income added successfully!")));
            _amountController.clear(); // Clear the amount input field after submission
            _sourceController.clear(); // Clear the source input field after submission
            _dateController.clear(); // Clear the date input field after submission
          } else {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Failed to add income: ${data['message']}")));
          }
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Failed to add income")));
        }
      } catch (e) {
        print("Error: $e");
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _loadUserId();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Add Income'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            children: <Widget>[
              // Budget Selection
              Text(
                "Select Budget",
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 10),
              DropdownButtonFormField<String>(
                value: selectedBudget,
                hint: const Text('Select Your Budget'),
                items: budgets.map((budget) {
                  return DropdownMenuItem<String>(
                    value: budget['budgetID'].toString(),
                    child: Text(budget['budgetName']),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() {
                    selectedBudget = value;
                  });
                },
                validator: (value) {
                  if (value == null) {
                    return 'Please select a budget';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Amount Input Field
              TextFormField(
                controller: _amountController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: "Amount",
                  border: OutlineInputBorder(),
                  prefixText: 'RM ',
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter an amount';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Income Source Input Field (Allow free text)
              TextFormField(
                controller: _sourceController,
                decoration: InputDecoration(
                  labelText: "Income Source",
                  hintText: 'Enter income source',  // Placeholder text
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter a source';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Date Input Field
              TextFormField(
                controller: _dateController,
                decoration: const InputDecoration(
                  labelText: "Date",
                  border: OutlineInputBorder(),
                ),
                readOnly: true,
                onTap: () => _selectDate(context),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please select a date';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Submit Button
              ElevatedButton(
                onPressed: _submitIncome,
                child: const Text('Add Income'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
