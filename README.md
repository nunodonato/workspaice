# WorkspAIce

WorkspAIce is a collaborative workspace where human and AI work together to build things. It's built with Laravel, Livewire, and Tailwind CSS.

## Setup Instructions

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/workspaice.git
   cd workspaice
   ```

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Install JavaScript dependencies:
   ```
   npm install
   ```

4. Copy the `.env.example` file to `.env` and configure your environment variables:
   ```
   cp .env.example .env
   ```

5. Generate an application key:
   ```
   php artisan key:generate
   ```

6. Run database migrations:
   ```
   php artisan migrate
   ```

7. Compile assets:
   ```
   npm run dev
   ```

8. Start the development server:
   ```
   php artisan serve
   ```

## Usage

1. Create a new project:
   - Navigate to the project creation page
   - Fill in the project name and description
   - Click "Create Project"

2. View a project:
   - Click on a project in the project list
   - You'll see the project details in the sidebar and the chat interface

3. Chat in a project:
   - In the project view, use the chat interface to send and receive messages
   - Messages are updated in real-time

## Components

1. ProjectCreation: Handles the creation of new projects
2. ProjectChat: Manages the chat functionality within a project
3. ProjectSidebar: Displays project details in the sidebar

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.