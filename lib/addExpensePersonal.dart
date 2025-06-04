import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'config.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';  // For image picking
import 'dart:io';  // For File class
import 'package:flutter_tesseract_ocr/flutter_tesseract_ocr.dart';  // For OCR text extraction

class AddPersonalExpenseScreen extends StatefulWidget {
  const AddPersonalExpenseScreen({Key? key}) : super(key: key);

  @override
  _AddPersonalExpenseScreenState createState() => _AddPersonalExpenseScreenState();
}

class _AddPersonalExpenseScreenState extends State<AddPersonalExpenseScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _amountController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _dateController = TextEditingController();
  String? selectedCategory;
  File? _receiptImage;
  final picker = ImagePicker();  // Image Picker
  List<dynamic> categories = [];  // List to hold categories
  List<dynamic> budgets = []; // List to hold budget options
  String? selectedBudget; // Variable to store the selected budget
  String _ocrText = "";  // Store OCR text from the image
  int userId = 0; // Default userId to 0 if not found
  String username = 'Loading...'; // To track username

  // Fetch session ID
  Future<String?> getSessionId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? sessionId = prefs.getString('session_id');  // Retrieve the session ID
    print('Session ID retrieved: $sessionId');  // Debugging: Check if session ID is correctly retrieved
    return sessionId;
  }

  // Load userId from SharedPreferences
  Future<void> _loadUserId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    int? storedUserId = prefs.getInt('userId');  // Retrieve userId from SharedPreferences

    print('Loaded userId: $storedUserId');  // Debugging output

    if (storedUserId != null) {
      setState(() {
        userId = storedUserId;  // Set the userId
      });
      fetchBudgets(userId);  // Fetch budgets after userID is loaded
    } else {
      setState(() {
        userId = 0;  // Default value if no userId is found
      });
    }
  }


  // Fetch categories for personal expenses
  Future<void> fetchCategories() async {
    final url = '${CONFIG.SERVER}/getCategories.php';  // Correct URL for your API
    try {
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        setState(() {
          categories = data['categories'];  // Ensure this is an array
        });
      } else {
        throw Exception('Failed to load categories');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
    }
  }

  // Fetch budgets by user ID
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

  // Pick image for the receipt
  Future<void> _pickImage() async {
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);
    if (pickedFile != null) {
      setState(() {
        _receiptImage = File(pickedFile.path);
      });

      // Extract text from the selected image using Tesseract OCR
      String text = await FlutterTesseractOcr.extractText(
        pickedFile.path,
        language: 'eng',  // or use 'eng+mal' if needed
      );

      setState(() {
        _ocrText = text;  // Store OCR text
        _descriptionController.text = text;  // Auto-fill description with OCR text
        _autoFillFields();  // Auto-fill amount and date
      });
    }
  }

  // Auto-fill amount and date using OCR text
  void _autoFillFields() {
    if (_ocrText.isNotEmpty) {
      _extractAmount(_ocrText);
      _extractDate(_ocrText);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("No OCR text found.")));
    }
  }

  // Extract Amount from OCR text
  void _extractAmount(String text) {
    RegExp amountRegExp = RegExp(r'Total\s?Amount\s?:?\s?([0-9]+\.[0-9]{2})');  // Capture total amount
    var match = amountRegExp.firstMatch(text);
    if (match != null) {
      setState(() {
        _amountController.text = 'RM ${match.group(1)}';  // Auto-fill amount
      });
    }
  }

  // Extract Date from OCR text
  void _extractDate(String text) {
    RegExp dateRegExp = RegExp(r'(\d{2}/\d{2}/\d{4})');  // Capture date (dd/mm/yyyy)
    var match = dateRegExp.firstMatch(text);
    if (match != null) {
      setState(() {
        _dateController.text = match.group(0)!;  // Auto-fill date
      });
    }
  }

  // Function to select a date using a calendar
  Future<void> _selectDate(BuildContext context) async {
    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null && pickedDate != DateTime.now()) {
      setState(() {
        _dateController.text = pickedDate.toLocal().toString().split(' ')[0]; // Format date as 'yyyy-MM-dd'
      });
    }
  }

  // Submit the form with the selected data
  Future<void> _submitForm() async {
    if (_formKey.currentState!.validate()) {
      final amount = _amountController.text;
      final description = _descriptionController.text;
      final date = _dateController.text;

      if (selectedCategory == null || selectedBudget == null) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Please select category and budget")));
        return;
      }

      if (amount.isEmpty || description.isEmpty || date.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Please fill in all fields.")));
        return;
      }

      final uri = Uri.parse('${CONFIG.SERVER}/addPersonalExpense.php');
      var request = http.MultipartRequest('POST', uri);

      //Set PHPSESSID as a cookie
      String? sessionId = await getSessionId();
      if (sessionId != null) {
        request.headers['Cookie'] = 'PHPSESSID=$sessionId';
      } else {
        print('Session ID is missing');
      }

      // Add fields
      request.fields['category_id'] = selectedCategory!;
      request.fields['amount'] = amount;
      request.fields['description'] = description;
      request.fields['date'] = date;
      request.fields['budget_id'] = selectedBudget!;

      // Add receipt image if exists
      if (_receiptImage != null) {
        var pic = await http.MultipartFile.fromPath('receipt_image', _receiptImage!.path);
        request.files.add(pic);
        print("File added: ${_receiptImage!.path}");
      }

      try {
        var response = await request.send();
        final responseData = await response.stream.bytesToString();

        print('Response status: ${response.statusCode}');
        print('Response body: $responseData');

        if (response.statusCode == 200) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Expense added successfully!")));
          // Optionally update budget
          _updateBudget(selectedBudget!);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Failed to add expense")));
        }
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error: $e")));
        print('Error: $e');
      }
    }
  }


  // Add the method to update the budget
  Future<void> _updateBudget(String budgetId) async {
    try {
      String? sessionId = await getSessionId();
      if (sessionId == null) {
        print('Session ID is missing');
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Session ID is missing")));
        return;
      }

      final updateUrl = '${CONFIG.SERVER}/updateBudget.php';
      final response = await http.post(Uri.parse(updateUrl), body: {
        'budgetID': budgetId,
      }, headers: {
        'Cookie': 'PHPSESSID=$sessionId',
      });

      print('Update response: ${response.statusCode}');
      print('Body: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Budget updated successfully.")));
          // Optional: Use data['balance'], etc.
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? "Failed to update budget.")));
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Failed to update budget.")));
      }
    } catch (e) {
      print('Error updating budget: $e');
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Error updating budget: $e")));
    }
  }


  @override
  void initState() {
    super.initState();
    fetchCategories();  // Fetch categories for personal expenses when the screen loads
    _loadUserId();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Add Personal Expense'),
      ),
      body: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 24.0),
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[

                // Upload Receipt Button
                ElevatedButton(
                  onPressed: _pickImage,
                  style: ElevatedButton.styleFrom(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: const Text('Upload Receipt Image'),
                ),
                const SizedBox(height: 20),

                // Show uploaded image with a cancel button
                if (_receiptImage != null) ...[
                  Card(
                    elevation: 5,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Image.file(
                            _receiptImage!,
                            height: 150,
                            width: double.infinity,
                            fit: BoxFit.cover,
                          ),
                        ),
                        TextButton.icon(
                          onPressed: () {
                            setState(() {
                              _receiptImage = null;
                              _ocrText = '';
                              _descriptionController.clear();
                              _amountController.clear();
                              _dateController.clear();
                            });
                          },
                          icon: const Icon(Icons.cancel, color: Colors.red),
                          label: const Text('Cancel Upload', style: TextStyle(color: Colors.red)),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                ],


                Text("Select Category", style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                const SizedBox(height: 10),
                DropdownButtonFormField<String>(
                  value: selectedCategory,
                  hint: const Text('Select Category'),
                  items: categories.map((category) {
                    return DropdownMenuItem<String>(
                      value: category['categoryID'].toString(),
                      child: Text(category['categoryName']),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setState(() {
                      selectedCategory = value;
                    });
                  },
                  validator: (value) {
                    if (value == null) {
                      return 'Please select a category';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                Text("Select Budget", style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                const SizedBox(height: 10),
                DropdownButtonFormField<String>(
                  value: selectedBudget,
                  hint: const Text('Select Budget'),
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

                TextFormField(
                  controller: _descriptionController,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: "Description",
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter a description';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),

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

                ElevatedButton(
                  onPressed: _submitForm,
                  style: ElevatedButton.styleFrom(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: const Text('Submit Expense'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
