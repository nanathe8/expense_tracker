import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
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
  String? _incomeSource; // To store selected income source

  // List of predefined income sources
  List<String> incomeSources = [
    'Salary',
    'Freelance',
    'Investment',
    'Business',
    'Rental',
    'Other'
  ];

  // Submit the income data
  Future<void> _submitIncome() async {
    if (_formKey.currentState!.validate()) {
      final amount = _amountController.text;
      final source = _incomeSource ?? 'Other'; // If no source selected, set to "Other"
      final date = _dateController.text;

      try {
        // Prepare the data to send to the PHP backend
        final response = await http.post(
          Uri.parse('${CONFIG.SERVER}/income.php'), // Replace with your PHP URL
          body: {
            'amount': amount,
            'source': source,
            'date': date,
          },
        );

        if (response.statusCode == 200) {
          // Ensure the response is valid JSON
          final data = json.decode(response.body);

          if (data['status'] == 'success') {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'])));
          } else {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'])));
          }
        } else {
          throw Exception('Failed to add income');
        }
      } catch (e) {
        // Handle error (e.g., network issue or invalid response)
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
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
              // Amount Input
              TextFormField(
                controller: _amountController,
                decoration: InputDecoration(labelText: 'Amount (RM)'),
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter an amount';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Source Dropdown (Income Source)
              DropdownButtonFormField<String>(
                value: _incomeSource,
                hint: Text('Select Income Source'),
                onChanged: (String? newValue) {
                  setState(() {
                    _incomeSource = newValue;
                  });
                },
                items: incomeSources.map((String source) {
                  return DropdownMenuItem<String>(
                    value: source,
                    child: Text(source),
                  );
                }).toList(),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please select a source';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Date Input
              TextFormField(
                controller: _dateController,
                decoration: InputDecoration(labelText: 'Date'),
                keyboardType: TextInputType.datetime,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter a date';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Submit Button
              ElevatedButton(
                onPressed: _submitIncome,
                style: ElevatedButton.styleFrom(backgroundColor: Colors.deepPurple),
                child: const Text("Add Income", style: TextStyle(fontSize: 16)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
