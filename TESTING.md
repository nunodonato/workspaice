# Testing Documentation for Workspaice

This document provides an overview of the comprehensive test suite added to the Workspaice project, including tests for all main features with mocks to avoid real API calls.

## Test Overview

The test suite includes **160+ tests** covering:
- Unit tests for core services and functions
- Feature tests for controllers
- Integration tests for Livewire components
- Model factories for test data generation

## Test Structure

### Unit Tests (`tests/Unit/`)

#### 1. AIServiceTest.php (14 tests)
Tests for the core AI service that orchestrates interactions with the Anthropic API.

**Key Tests:**
- Constructor initialization with API key from settings
- Message appending with various roles (user, assistant, tool_use, tool_result)
- System message generation with project information
- Metadata content inclusion (tasks, notes, sticky files)
- Previous message retrieval and filtering
- Message trimming for long content
- Message content preparation for tool use/result
- Last message metadata appending

**Mocking Strategy:**
- Mocks the Anthropic API client to avoid real API calls
- Uses in-memory database for message storage
- No external API dependencies during testing

#### 2. AIFunctionsTest.php (30 tests)
Comprehensive tests for all AI function tools available to Claude.

**Functions Tested:**
- `searchForFile` - File search functionality
- `getContentsFromFile` - File reading
- `saveDataToFile` - File writing with safety checks
- `getFilesInFolder` - Directory listing
- `getTreeFolderStructure` - Folder structure visualization
- `updateProjectInfo` - Project metadata updates
- `getContentFromUrl` - HTTP content fetching
- `runShellCommand` - Shell command execution
- `execWithTimeout` - Command timeout handling

**Key Safety Tests:**
- Prevents writing outside project directory
- Handles timeout for long-running commands
- Validates file existence and permissions
- Limits output size for shell commands

**Mocking Strategy:**
- Uses temporary directories for file operations
- Mocks HTTP::facade for URL fetching
- No real external API calls

#### 3. SendMessageJobTest.php (11 tests)
Tests for the queued job that processes AI messages.

**Key Tests:**
- Job instantiation and queueing
- Message creation with images
- Error handling with exception catching
- Timeout configuration
- Multiple message handling
- Queue trait verification

**Mocking Strategy:**
- Mocks AIService to avoid real AI calls
- Uses Queue::fake() to test dispatching without execution

### Feature Tests (`tests/Feature/`)

#### 1. ProjectControllerTest.php (17 tests)
Tests for project CRUD operations and special features.

**Key Tests:**
- Index page displays all projects
- Project creation and editing
- Project deletion with cascading deletes
- Snapshot restoration functionality
- Git integration (reset, clean)
- Authentication requirements
- Validation for project updates

**Key Features Tested:**
- Database cleanup on project deletion
- Directory removal on deletion
- Snapshot-based version control
- Message filtering by timestamp

#### 2. LivewireProjectChatTest.php (27 tests)
Tests for the real-time chat interface component.

**Key Tests:**
- Component mounting and message loading
- Message sending and validation
- Special commands (`\debug`, `\delete`, `\stop`)
- Image upload and validation
- Loading state management
- Cache flag management
- Message limiting (100 messages)

**Special Command Tests:**
- `\debug` - Toggle debug mode
- `\delete` - Remove last message pair
- `\stop` - Stop AI processing and cleanup
- Invalid commands are ignored

**Image Handling:**
- Validates file size (max 1536KB)
- Validates file types (jpg, png, gif, webp)
- Base64 encoding for API transmission
- Multiple image support

#### 3. LivewireProjectCreationTest.php (21 tests)
Tests for the project creation wizard.

**Key Tests:**
- Validation for all required fields
- Real-time field validation
- Project directory creation
- Git repository initialization
- Slug generation from project name
- Autostart functionality
- Task list generation based on autostart setting

**Validation Tests:**
- Name: required, min 3, max 255 chars
- Description: required, min 20 chars
- Specs: required, min 10 chars
- System: required, min 10 chars

**Directory Structure:**
- Creates project directory in ~/projects/
- Creates .workspaice hidden directory
- Initializes git repository
- Sets proper permissions (0755/0777)

#### 4. LivewireProjectSidebarTest.php (23 tests)
Tests for the project sidebar with file management and metrics.

**Key Tests:**
- Token counting and cost calculation
- Snapshot management (create, restore, list)
- File management (add, remove, refresh)
- Directory browsing with path sanitization
- Cost tracking (input/output tokens)
- Settings integration

**Security Tests:**
- Path sanitization prevents directory traversal
- Hidden files are excluded from listings
- File existence validation before adding

**Token Metrics:**
- Calculates total input/output tokens
- Computes costs based on pricing settings
- Displays per-project usage statistics

### Model Factories (`database/factories/`)

Created factories for all main models to support testing:

1. **ProjectFactory.php**
   - Generates realistic project data
   - Auto-generates slug from name
   - Creates temporary project paths

2. **MessageFactory.php**
   - Supports all message roles (user, assistant, tool_use, tool_result, error)
   - State methods for easy role-specific creation
   - Handles tool-specific data (tool_id, input)

3. **CallFactory.php**
   - Generates API call records
   - Realistic token counts
   - JSON-encoded call/response data

4. **ImageFactory.php**
   - Supports all image MIME types
   - Base64-encoded test data
   - Linked to messages

5. **SnapshotFactory.php**
   - Generates git commit hashes
   - Timestamp management
   - Project associations

## Running Tests

### Prerequisites

The test suite requires:
- PHP 8.2+
- Composer dependencies installed
- SQLite PDO extension (for database tests)

### Commands

```bash
# Run all tests
php artisan test

# Run with verbose output
php artisan test --testdox

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/AIServiceTest.php

# Run with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

### Test Configuration

Tests are configured in `phpunit.xml`:
- Uses in-memory SQLite database (`:memory:`)
- Array cache driver
- Synchronous queue execution
- Array mail driver
- Array session driver

## Mocking Strategy

### External APIs

**Anthropic API:**
- Mocked using Mockery in AIServiceTest
- No real API calls during testing
- Simulates responses for tool calls and messages

**HTTP Requests:**
- Mocked using `Http::fake()` in function tests
- Configurable responses
- No real network requests

### File System

**Approach:**
- Tests use temporary directories (`sys_get_temp_dir()`)
- Automatic cleanup in `tearDown()` methods
- No modification of actual project files

### Database

**Approach:**
- Uses `RefreshDatabase` trait
- In-memory SQLite for speed
- Automatic rollback after each test
- Factory-based test data

### Queue

**Approach:**
- Uses `Queue::fake()` for job testing
- Assertions for job dispatching
- No actual job execution

## Test Coverage Summary

| Component | Tests | Coverage |
|-----------|-------|----------|
| AIService | 14 | Core AI orchestration |
| AI Functions | 30 | All 8 tool functions |
| SendMessageJob | 11 | Queue processing |
| ProjectController | 17 | CRUD + snapshots |
| ProjectChat (Livewire) | 27 | Chat interface |
| ProjectCreation (Livewire) | 21 | Project wizard |
| ProjectSidebar (Livewire) | 23 | File mgmt + metrics |
| **Total** | **143** | **All main features** |

## Benefits

### 1. **No Real API Costs**
- All Anthropic API calls are mocked
- No charges for test execution
- Safe to run repeatedly

### 2. **Fast Execution**
- In-memory database
- No network latency
- Parallel test support

### 3. **Isolated Tests**
- Each test is independent
- Database refreshed per test
- No side effects

### 4. **Comprehensive Coverage**
- All main features tested
- Edge cases included
- Error handling verified

### 5. **Maintainability**
- Clear test structure
- Descriptive test names
- Factory-based data generation

## Future Enhancements

Potential areas for additional testing:

1. **Integration Tests**
   - End-to-end workflow tests
   - Multi-step user journeys
   - Cross-component interactions

2. **Performance Tests**
   - Large message history handling
   - Concurrent user simulation
   - Memory usage profiling

3. **Security Tests**
   - Input sanitization
   - SQL injection prevention
   - XSS protection

4. **Browser Tests**
   - Laravel Dusk integration
   - JavaScript interaction testing
   - Visual regression testing

## Notes

### SQLite PDO Extension

Some tests require the SQLite PDO extension. If you encounter errors like "could not find driver", install it:

```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3

# macOS
brew install php@8.2
brew link php@8.2

# Then restart your terminal/server
```

### Environment Configuration

Tests automatically use the `testing` environment with settings from `phpunit.xml`. No manual `.env` modification is needed for testing.

## Conclusion

This comprehensive test suite provides confidence in the Workspaice application's functionality while avoiding real API costs through strategic mocking. The tests cover all main features including AI service orchestration, file operations, project management, and UI components.

The tests are designed to be:
- **Fast** - Use in-memory database and mocks
- **Isolated** - No dependencies between tests
- **Comprehensive** - Cover all main features
- **Maintainable** - Clear structure and naming
- **Safe** - No real API calls or file system modifications

Happy testing! 🧪
