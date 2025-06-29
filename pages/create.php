<?php
require_once '../includes/db.php';
require_once '../database/init.php';

$success = false;
$error = '';
$pasteId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $language = $_POST['language'] ?? 'text';
    $expiration = $_POST['expiration'] ?? null;
    $visibility = $_POST['visibility'] ?? 'public';
    $password = trim($_POST['password'] ?? '');
    $burnAfterRead = isset($_POST['burn_after_read']);
    $zeroKnowledge = isset($_POST['zero_knowledge']);
    
    if (empty($content)) {
        $error = 'Content cannot be empty.';
    } else {
        // Set expiration datetime if specified
        $expirationDate = null;
        if ($expiration && $expiration !== 'never') {
            switch($expiration) {
                case '10 minutes':
                    $expirationDate = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    break;
                case '1 hour':
                    $expirationDate = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    break;
                case '1 day':
                    $expirationDate = date('Y-m-d H:i:s', strtotime('+1 day'));
                    break;
                case '1 week':
                    $expirationDate = date('Y-m-d H:i:s', strtotime('+1 week'));
                    break;
                case '1 month':
                    $expirationDate = date('Y-m-d H:i:s', strtotime('+1 month'));
                    break;
                case '6 months':
                    $expirationDate = date('Y-m-d H:i:s', strtotime('+6 months'));
                    break;
                case '1 year':
                    $expirationDate = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;
                default:
                    $expirationDate = null;
            }
        }
        
        // Hash password if provided
        $hashedPassword = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
        
        $result = createPasteAdvanced($title, $content, $language, $expirationDate, $visibility, $hashedPassword, $burnAfterRead, $zeroKnowledge);
        
        if ($result) {
            // Handle different return formats for compatibility
            if (is_array($result)) {
                $pasteId = $result['id'];
                $creatorToken = $result['creator_token'];
            } else {
                // Backward compatibility for non-burn pastes
                $pasteId = $result;
                $creatorToken = null;
            }
            
            // For burn after read pastes, add secure creator token
            if ($burnAfterRead && $creatorToken) {
                header("Location: view.php?id=" . $pasteId . "&creator=" . $creatorToken);
            } else {
                header("Location: view.php?id=" . $pasteId);
            }
            exit();
        } else {
            $error = 'Failed to create paste. Please try again.';
        }
    }
}

$pageTitle = "Create New Paste";
include '../includes/header.php';
?>

<main class="container-fluid px-4 py-5">
    <?php if ($success): ?>
    <!-- Success Message -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> Your paste has been created successfully.
                <div class="mt-2">
                    <a href="view.php?id=<?php echo htmlspecialchars($pasteId); ?>" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-eye me-1"></i>View Paste
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <!-- Error Message -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Create Paste Form -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg">
                <div class="card-header border-0 bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-plus me-2"></i>Create Paste
                        </h4>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-light btn-sm" id="loadTemplateBtn">
                                <i class="fas fa-file-code me-1"></i><span class="d-none d-sm-inline">Load </span>Template
                            </button>
                            <button type="button" class="btn btn-outline-light btn-sm" id="importFileBtn">
                                <i class="fas fa-upload me-1"></i><span class="d-none d-sm-inline">Import </span>File
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="needs-validation" novalidate id="pasteForm">
                        <div class="row">
                            <!-- Left Column - Basic Fields -->
                            <div class="col-md-8">
                                <!-- Title -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-file-signature me-1"></i>Title (Optional)
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           placeholder="Enter a title for your paste..."
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                </div>

                                <!-- Language Selection -->
                                <div class="mb-3">
                                    <label for="language" class="form-label">
                                        <i class="fas fa-code me-1"></i>Language
                                    </label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="text">Plain Text</option>
                                        <option value="php">PHP</option>
                                        <option value="javascript">JavaScript</option>
                                        <option value="typescript">TypeScript</option>
                                        <option value="python">Python</option>
                                        <option value="java">Java</option>
                                        <option value="c">C</option>
                                        <option value="cpp">C++</option>
                                        <option value="csharp">C#</option>
                                        <option value="html">HTML</option>
                                        <option value="css">CSS</option>
                                        <option value="sql">SQL</option>
                                        <option value="json">JSON</option>
                                        <option value="xml">XML</option>
                                        <option value="yaml">YAML</option>
                                        <option value="markdown">Markdown</option>
                                        <option value="ruby">Ruby</option>
                                        <option value="go">Go</option>
                                        <option value="rust">Rust</option>
                                        <option value="swift">Swift</option>
                                        <option value="kotlin">Kotlin</option>
                                        <option value="scala">Scala</option>
                                        <option value="shell">Shell</option>
                                        <option value="dockerfile">Dockerfile</option>
                                    </select>
                                </div>

                                <!-- Content -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="content" class="form-label mb-0">
                                            <i class="fas fa-file-code me-1"></i>Content *
                                        </label>
                                        <small class="text-muted">
                                            <span id="charCount">0</span> characters
                                        </small>
                                    </div>
                                    <div class="mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="zeroKnowledge" name="zero_knowledge">
                                            <label class="form-check-label small" for="zeroKnowledge">
                                                <i class="fas fa-shield-alt me-1 text-warning"></i>Zero Knowledge Encryption
                                                <i class="fas fa-info-circle ms-1 text-muted" 
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-placement="top" 
                                                   data-bs-title="Zero Knowledge Encryption encrypts your content in the browser before sending it to the server. The server never sees your original text, ensuring maximum privacy. The decryption key is included in the URL fragment."></i>
                                            </label>
                                        </div>
                                    </div>
                                    <div id="zeroKnowledgeWarning" class="alert alert-warning alert-sm mb-2" style="display: none;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <small><strong>Zero Knowledge Mode:</strong> Your content will be encrypted before sending to the server. Make sure to save the generated URL as it cannot be recovered if lost.</small>
                                    </div>
                                    <textarea class="form-control font-monospace" id="content" name="content" 
                                              rows="20" placeholder="Paste your code or text here..." 
                                              required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                    <div class="invalid-feedback">
                                        Please provide some content for your paste.
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Advanced Options -->
                            <div class="col-md-4">
                                <!-- Visibility Options -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-eye me-1"></i>Visibility
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="visibility" id="visibilityPublic" value="public" checked>
                                        <label class="form-check-label" for="visibilityPublic">
                                            <i class="fas fa-globe me-1"></i>Public
                                            <small class="d-block text-muted">Visible to everyone</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="visibility" id="visibilityUnlisted" value="unlisted">
                                        <label class="form-check-label" for="visibilityUnlisted">
                                            <i class="fas fa-link me-1"></i>Unlisted
                                            <small class="d-block text-muted">Only via direct link</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="visibility" id="visibilityPrivate" value="private">
                                        <label class="form-check-label" for="visibilityPrivate">
                                            <i class="fas fa-lock me-1"></i>Private
                                            <small class="d-block text-muted">Login required</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Expiration -->
                                <div class="mb-3">
                                    <label for="expiration" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Expiration
                                    </label>
                                    <select class="form-select" id="expiration" name="expiration">
                                        <option value="never">Never</option>
                                        <option value="10 minutes">10 Minutes</option>
                                        <option value="1 hour">1 Hour</option>
                                        <option value="1 day">1 Day</option>
                                        <option value="1 week">1 Week</option>
                                        <option value="1 month">1 Month</option>
                                        <option value="6 months">6 Months</option>
                                        <option value="1 year">1 Year</option>
                                    </select>
                                </div>

                                <!-- Password Protection -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-key me-1"></i>Password Protection
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Optional password">
                                    <div class="form-text">Leave empty for no password protection</div>
                                </div>

                                <!-- Advanced Options -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-cog me-1"></i>Advanced Options
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="burnAfterRead" name="burn_after_read">
                                        <label class="form-check-label" for="burnAfterRead">
                                            <i class="fas fa-fire me-1 text-danger"></i>Burn After Read
                                            <small class="d-block text-muted">Delete after first view</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Submit Buttons -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Create Paste
                                    </button>
                                    <a href="../index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Load Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="list-group" id="templateCategories">
                                <button type="button" class="list-group-item list-group-item-action active" data-category="web">
                                    <i class="fas fa-globe me-2"></i>Web Development
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-category="backend">
                                    <i class="fas fa-server me-2"></i>Backend
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-category="mobile">
                                    <i class="fas fa-mobile-alt me-2"></i>Mobile
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-category="data">
                                    <i class="fas fa-chart-bar me-2"></i>Data Science
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-category="config">
                                    <i class="fas fa-cog me-2"></i>Configuration
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div id="templateContent">
                                <!-- Templates will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File Import Input -->
    <input type="file" id="fileImport" accept=".txt,.js,.py,.php,.html,.css,.json,.xml,.sql,.md,.cpp,.c,.java,.cs,.rb,.go,.rs,.ts,.sh" style="display: none;">

    <!-- Zero Knowledge Success Modal -->
    <div class="modal fade" id="zeroKnowledgeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i>Zero-Knowledge Paste Created!
                    </h5>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success d-flex align-items-center mb-3">
                        <i class="fas fa-check-circle me-2 fs-4"></i>
                        <div>
                            <strong>Zero-Knowledge paste created!</strong> Save this URL – it contains your decryption key.
                        </div>
                    </div>
                    
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                        <div>
                            <strong>This URL is required to view the paste. We cannot recover it if lost.</strong>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-2">Your Zero-Knowledge URL:</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="encryptedUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyEncryptedUrl" 
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Copy URL">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-success" id="viewZkePaste">
                        <i class="fas fa-eye me-1"></i>View Paste
                    </button>
                    <button type="button" class="btn btn-secondary" id="createAnotherPaste">
                        <i class="fas fa-plus me-1"></i>Create Another
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Character counter
document.getElementById('content').addEventListener('input', function() {
    updateCharCount();
});

// Template system
const templates = {
    web: [
        {
            title: 'HTML5 Boilerplate',
            language: 'html',
            content: `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Hello World</h1>
</body>
</html>`
        },
        {
            title: 'React Component',
            language: 'javascript',
            content: `import React, { useState } from 'react';

const MyComponent = () => {
    const [count, setCount] = useState(0);

    return (
        <div>
            <h1>Count: {count}</h1>
            <button onClick={() => setCount(count + 1)}>
                Increment
            </button>
        </div>
    );
};

export default MyComponent;`
        }
    ],
    backend: [
        {
            title: 'Express.js Server',
            language: 'javascript',
            content: `const express = require('express');
const app = express();
const port = 3000;

app.use(express.json());

app.get('/', (req, res) => {
    res.json({ message: 'Hello World!' });
});

app.listen(port, () => {
    console.log(\`Server running at http://localhost:\${port}\`);
});`
        },
        {
            title: 'Flask Application',
            language: 'python',
            content: `from flask import Flask, jsonify

app = Flask(__name__)

@app.route('/')
def hello_world():
    return jsonify(message='Hello, World!')

@app.route('/api/data')
def get_data():
    return jsonify(data={'key': 'value'})

if __name__ == '__main__':
    app.run(debug=True)`
        }
    ],
    mobile: [
        {
            title: 'React Native Component',
            language: 'javascript',
            content: `import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';

const MyComponent = () => {
    return (
        <View style={styles.container}>
            <Text style={styles.title}>Hello Mobile!</Text>
            <TouchableOpacity style={styles.button}>
                <Text style={styles.buttonText}>Press Me</Text>
            </TouchableOpacity>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    title: {
        fontSize: 24,
        marginBottom: 20,
    },
    button: {
        backgroundColor: '#007AFF',
        padding: 15,
        borderRadius: 8,
    },
    buttonText: {
        color: 'white',
        fontSize: 16,
    },
});

export default MyComponent;`
        }
    ],
    data: [
        {
            title: 'Python Data Analysis',
            language: 'python',
            content: `import pandas as pd
import numpy as np
import matplotlib.pyplot as plt

# Load data
df = pd.read_csv('data.csv')

# Basic statistics
print(df.describe())

# Data visualization
plt.figure(figsize=(10, 6))
df['column'].hist(bins=30)
plt.title('Data Distribution')
plt.xlabel('Value')
plt.ylabel('Frequency')
plt.show()

# Data cleaning
df_clean = df.dropna()
df_clean = df_clean[df_clean['column'] > 0]

print(f"Original rows: {len(df)}")
print(f"Clean rows: {len(df_clean)}")`
        }
    ],
    config: [
        {
            title: 'Docker Compose',
            language: 'yaml',
            content: `version: '3.8'

services:
  web:
    build: .
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
    depends_on:
      - db

  db:
    image: postgres:13
    environment:
      POSTGRES_DB: myapp
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    volumes:
      - postgres_data:/var/lib/postgresql/data

volumes:
  postgres_data:`
        },
        {
            title: 'package.json',
            language: 'json',
            content: `{
  "name": "my-project",
  "version": "1.0.0",
  "description": "A sample project",
  "main": "index.js",
  "scripts": {
    "start": "node index.js",
    "dev": "nodemon index.js",
    "test": "jest"
  },
  "dependencies": {
    "express": "^4.18.0"
  },
  "devDependencies": {
    "nodemon": "^2.0.0",
    "jest": "^28.0.0"
  },
  "keywords": ["node", "express"],
  "author": "Your Name",
  "license": "MIT"
}`
        }
    ]
};

// Template modal functionality
document.getElementById('loadTemplateBtn').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    loadTemplateCategory('web');
    modal.show();
});

document.getElementById('templateCategories').addEventListener('click', function(e) {
    if (e.target.classList.contains('list-group-item')) {
        // Remove active class from all items
        document.querySelectorAll('#templateCategories .list-group-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active class to clicked item
        e.target.classList.add('active');
        
        // Load templates for this category
        const category = e.target.getAttribute('data-category');
        loadTemplateCategory(category);
    }
});

function loadTemplateCategory(category) {
    const content = document.getElementById('templateContent');
    const categoryTemplates = templates[category] || [];
    
    content.innerHTML = categoryTemplates.map(template => `
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title">${template.title}</h6>
                        <small class="text-muted">${template.language}</small>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="selectTemplate('${category}', '${template.title}')">
                        Use Template
                    </button>
                </div>
                <pre class="mt-2 small text-muted" style="max-height: 100px; overflow: hidden;"><code>${template.content.substring(0, 200)}...</code></pre>
            </div>
        </div>
    `).join('');
}

function selectTemplate(category, title) {
    const template = templates[category].find(t => t.title === title);
    if (template) {
        document.getElementById('title').value = template.title;
        document.getElementById('content').value = template.content;
        document.getElementById('language').value = template.language;
        
        // Update character count
        updateCharCount();
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
    }
}

// File import functionality
document.getElementById('importFileBtn').addEventListener('click', function() {
    document.getElementById('fileImport').click();
});

document.getElementById('fileImport').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const content = e.target.result;
            const fileName = file.name;
            const extension = fileName.split('.').pop().toLowerCase();
            
            // Set content
            document.getElementById('content').value = content;
            updateCharCount();
            
            // Set title if empty
            if (!document.getElementById('title').value) {
                document.getElementById('title').value = fileName;
            }
            
            // Auto-detect language
            const languageMap = {
                'js': 'javascript',
                'ts': 'typescript',
                'py': 'python',
                'php': 'php',
                'html': 'html',
                'css': 'css',
                'json': 'json',
                'xml': 'xml',
                'sql': 'sql',
                'md': 'markdown',
                'cpp': 'cpp',
                'c': 'c',
                'java': 'java',
                'cs': 'csharp',
                'rb': 'ruby',
                'go': 'go',
                'rs': 'rust',
                'sh': 'shell'
            };
            
            if (languageMap[extension]) {
                document.getElementById('language').value = languageMap[extension];
            }
        };
        reader.readAsText(file);
    }
});

// Zero Knowledge encryption functionality
let isZeroKnowledgeEnabled = false;
let originalContent = '';

document.getElementById('zeroKnowledge').addEventListener('change', function() {
    isZeroKnowledgeEnabled = this.checked;
    const warningDiv = document.getElementById('zeroKnowledgeWarning');
    const contentTextarea = document.getElementById('content');
    
    if (this.checked) {
        // Show warning
        warningDiv.style.display = 'block';
        
        // Store original content if any exists
        originalContent = contentTextarea.value;
        
        // Add visual indicator to textarea
        contentTextarea.style.borderColor = '#ffc107';
        contentTextarea.style.backgroundColor = '#fff3cd';
        contentTextarea.style.color = '#856404'; // Dark brown text for good contrast
        
        // Show warning - encryption happens only during form submission
        if (originalContent.length > 0) {
            if (!confirm('Enabling Zero Knowledge encryption will encrypt your content during submission. Continue?')) {
                this.checked = false;
                isZeroKnowledgeEnabled = false;
                warningDiv.style.display = 'none';
                resetContentStyle();
            }
        }
    } else {
        // Hide warning
        warningDiv.style.display = 'none';
        
        // Reset content styling
        resetContentStyle();
        
        // If content was encrypted, ask if user wants to decrypt
        if (originalContent && contentTextarea.value !== originalContent) {
            if (confirm('Do you want to restore your original content?')) {
                contentTextarea.value = originalContent;
                updateCharCount();
            }
        }
    }
});

function resetContentStyle() {
    const contentTextarea = document.getElementById('content');
    contentTextarea.style.borderColor = '';
    contentTextarea.style.backgroundColor = '';
    contentTextarea.style.color = '';
}

// encryptContent() function removed to prevent double encryption
// Encryption now ONLY happens during form submission

function updateCharCount() {
    const content = document.getElementById('content').value;
    document.getElementById('charCount').textContent = content.length.toLocaleString();
}

// Real-time encryption on focus out - DISABLED to prevent double encryption
// The encryption now happens only during form submission
/*
document.getElementById('content').addEventListener('blur', async function() {
    if (isZeroKnowledgeEnabled && this.value !== originalContent) {
        originalContent = this.value;
        setTimeout(async () => {
            if (isZeroKnowledgeEnabled) {
                await encryptContent();
            }
        }, 500); // Small delay to allow user to see their content briefly
    }
});
*/

// Form submission with Zero Knowledge handling
document.addEventListener('DOMContentLoaded', function() {
    const pasteForm = document.getElementById('pasteForm');
    if (!pasteForm) {
        console.error('pasteForm element not found');
        return;
    }
    
    console.log('ZKE form handler attached successfully');
    
    pasteForm.addEventListener('submit', async function(e) {
    const zeroKnowledge = document.getElementById('zeroKnowledge').checked;
    
    if (zeroKnowledge) {
        e.preventDefault(); // Always prevent default for ZKE
        e.stopPropagation(); // Stop any other handlers
        
        // Clear any existing processing states
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Create Paste';
        }
        
        // Remove any modal backdrops that might exist
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        
        try {
            console.log('Starting ZKE encryption process...');
            
            // Check Web Crypto API support
            if (!window.crypto || !window.crypto.subtle) {
                throw new Error('Web Crypto API not supported in this browser');
            }
            
            console.log('Web Crypto API supported');
            
            // Get content to encrypt
            const content = document.getElementById('content').value;
            console.log('Content length:', content.length);
            
            if (!content.trim()) {
                throw new Error('Content cannot be empty');
            }
            
            // Generate 32-byte (256-bit) encryption key
            const key = new Uint8Array(32);
            window.crypto.getRandomValues(key);
            
            // Generate 12-byte IV for AES-GCM
            const iv = new Uint8Array(12);
            window.crypto.getRandomValues(iv);
            
            console.log('Generated key and IV');
            
            // Import key for Web Crypto API
            const cryptoKey = await window.crypto.subtle.importKey(
                'raw',
                key,
                { name: 'AES-GCM' },
                false,
                ['encrypt']
            );
            
            // Encrypt content using AES-GCM
            const encoder = new TextEncoder();
            const data = encoder.encode(content);
            
            const encryptedBuffer = await window.crypto.subtle.encrypt(
                {
                    name: 'AES-GCM',
                    iv: iv
                },
                cryptoKey,
                data
            );
            
            // Combine IV + encrypted data and convert to base64
            const combined = new Uint8Array(iv.length + encryptedBuffer.byteLength);
            combined.set(iv, 0);
            combined.set(new Uint8Array(encryptedBuffer), iv.length);
            
            const encryptedContent = btoa(String.fromCharCode(...combined));
            const keyBase64 = btoa(String.fromCharCode(...key));
            
            console.log('Content encrypted with AES-GCM, submitting form...');
            
            // Replace content with encrypted version
            document.getElementById('content').value = encryptedContent;
            
            // Submit form via fetch to avoid page navigation
            const formData = new FormData(this);
            
            console.log('Sending fetch request...');
            const response = await fetch(this.action || window.location.href, {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            console.log('Response redirected:', response.redirected);
            console.log('Response URL:', response.url);
            
            let pasteId = null;
            
            // Check if response was redirected (which means paste was created successfully)
            if (response.redirected) {
                console.log('Detected redirect to:', response.url);
                // Extract paste ID from redirected URL
                const redirectUrl = response.url;
                const pasteIdMatch = redirectUrl.match(/view\.php\?id=([a-zA-Z0-9]+)/);
                if (pasteIdMatch) {
                    pasteId = pasteIdMatch[1];
                    console.log('Extracted paste ID from redirect:', pasteId);
                }
            }
            
            if (pasteId) {
                // Generate the full ZKE URL with decryption key
                const zkeUrl = `${window.location.origin}/pages/view.php?id=${pasteId}#zk=${encodeURIComponent(keyBase64)}`;
                
                console.log('Generated ZKE URL:', zkeUrl);
                
                // Set the URL in the modal
                document.getElementById('encryptedUrl').value = zkeUrl;
                
                // Show the modal without any backdrop issues
                const modalElement = document.getElementById('zeroKnowledgeModal');
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: false,  // No backdrop to prevent graying
                    keyboard: true
                });
                
                // Remove any existing backdrop manually
                const existingBackdrop = document.querySelector('.modal-backdrop');
                if (existingBackdrop) {
                    existingBackdrop.remove();
                }
                
                modal.show();
                
                console.log('Modal shown successfully');
            } else {
                console.log('Failed to extract paste ID from response');
                throw new Error('Failed to extract paste ID from response - no redirect detected');
            }
            
        } catch (error) {
            console.error('ZKE encryption/submission error:', error);
            console.error('Error details:', error.message);
            console.error('Error stack:', error.stack);
            alert('Failed to create encrypted paste: ' + error.message);
        }
        
        return false;
    }
    });
});

// Copy encrypted URL
document.getElementById('copyEncryptedUrl').addEventListener('click', function() {
    const urlInput = document.getElementById('encryptedUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        this.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-copy"></i>';
        }, 2000);
    } catch (err) {
        console.error('Failed to copy URL:', err);
    }
});

// View ZKE Paste button
document.addEventListener('click', function(e) {
    if (e.target.id === 'viewZkePaste') {
        const url = document.getElementById('encryptedUrl').value;
        if (url) {
            window.location.href = url;
        }
    }
});

// Create Another Paste button  
document.addEventListener('click', function(e) {
    if (e.target.id === 'createAnotherPaste') {
        window.location.href = 'create.php';
    }
});

// Initialize character count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCharCount();
});
</script>

<?php include '../includes/footer.php'; ?>
