# Contributing to TagLock

Thank you for your interest in contributing to TagLock! We welcome contributions from the community.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Making Changes](#making-changes)
- [Submitting Pull Requests](#submitting-pull-requests)
- [Reporting Bugs](#reporting-bugs)
- [Feature Requests](#feature-requests)

## Code of Conduct

Be respectful and professional in all interactions. We're here to build great software together.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a new branch for your changes
4. Make your changes
5. Submit a pull request

## Development Setup

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 18+ and npm
- WordPress 6.8+ (for testing)

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/taglock.git
cd taglock

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Build assets
npm run build
```

### Development Commands

```bash
# Watch mode for development
npm run start

# Production build
npm run build

# Lint JavaScript
npm run lint:js

# Lint CSS
npm run lint:css

# Format code
npm run format
```

## Coding Standards

### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use strict types: `declare(strict_types=1);`
- Type hint all parameters and return types
- Write PHPDoc for all classes and methods
- Use dependency injection via Symfony DI

Example:
```php
<?php

declare(strict_types=1);

namespace TagLock\Service;

class ExampleService
{
    public function __construct(
        private readonly DependencyInterface $dependency
    ) {
    }

    public function doSomething(string $input): string
    {
        return $this->dependency->process($input);
    }
}
```

### JavaScript/React

- Use ES6+ syntax
- Follow WordPress coding standards for JavaScript
- Use functional components and hooks
- Use WordPress components (@wordpress/components) when possible
- Add PropTypes for component validation

Example:
```javascript
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import PropTypes from 'prop-types';

const ExampleComponent = ({ title, onAction }) => {
    const [count, setCount] = useState(0);

    return (
        <div>
            <h2>{title}</h2>
            <p>Count: {count}</p>
            <Button onClick={() => setCount(count + 1)}>
                Increment
            </Button>
        </div>
    );
};

ExampleComponent.propTypes = {
    title: PropTypes.string.isRequired,
    onAction: PropTypes.func,
};

export default ExampleComponent;
```

### CSS

- Use BEM naming convention
- Prefix all classes with `taglock-`
- Use CSS custom properties for spacing/colors
- Mobile-first approach

Example:
```css
.taglock-admin__feature {
    padding: var(--taglock-admin-gap);
    border-radius: var(--taglock-admin-radius);
}

.taglock-admin__feature-title {
    font-weight: 600;
    margin-bottom: 4px;
}
```

## Making Changes

### Branch Naming

- Feature: `feature/short-description`
- Bug fix: `fix/short-description`
- Documentation: `docs/short-description`

Examples:
- `feature/add-custom-redirect`
- `fix/connection-timeout`
- `docs/update-readme`

### Commit Messages

Write clear, descriptive commit messages:

```
Short summary (50 chars or less)

Detailed explanation if needed. Wrap at 72 characters.

- Bullet points for multiple changes
- Reference issues: Fixes #123
- Include context about why the change was made
```

Example:
```
Add connection health check scheduler

- Implement daily cron job to verify KlickTipp connection
- Add connection_status field to settings
- Display status badge in admin UI
- Fixes #42
```

### Testing

Before submitting a pull request:

1. **Test your changes** in a WordPress installation
2. **Check for PHP errors** - enable `WP_DEBUG`
3. **Test in different browsers** if UI changes
4. **Verify mobile responsiveness** for frontend changes
5. **Run linters**:
   ```bash
   npm run lint:js
   npm run lint:css
   ```

## Submitting Pull Requests

1. **Update your fork** with the latest changes from main:
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Push your changes** to your fork:
   ```bash
   git push origin feature/your-feature
   ```

3. **Create a Pull Request** on GitHub with:
   - Clear title describing the change
   - Description of what changed and why
   - Reference to related issues
   - Screenshots for UI changes
   - Confirmation that you've tested the changes

### Pull Request Template

```markdown
## Description
Brief description of what this PR does.

## Related Issues
Fixes #123

## Changes Made
- Change 1
- Change 2
- Change 3

## Testing
- [ ] Tested in WordPress 6.8+
- [ ] Tested in Chrome/Firefox/Safari
- [ ] Tested on mobile devices
- [ ] No PHP errors with WP_DEBUG enabled
- [ ] Linters pass

## Screenshots (if applicable)
Add screenshots for UI changes.
```

## Reporting Bugs

Found a bug? Please create an issue with:

1. **Clear title** describing the bug
2. **Steps to reproduce** the issue
3. **Expected behavior** vs actual behavior
4. **Environment details**:
   - WordPress version
   - PHP version
   - Browser (for frontend issues)
   - Plugin version
5. **Screenshots or error messages** if applicable

## Feature Requests

Have an idea? Create an issue with:

1. **Clear description** of the feature
2. **Use case** - why is this needed?
3. **Proposed implementation** (if you have ideas)
4. **Alternatives considered**

## Architecture Guidelines

### PHP Backend

- Use Symfony Dependency Injection container
- Separate concerns: Controllers, Services, Repositories
- Follow SOLID principles
- Use interfaces for contracts
- Enums for constants

### React Frontend

- Keep components small and focused
- Extract reusable logic into hooks
- Use WordPress components library
- Separate business logic from UI

### API Design

- RESTful endpoints under `/wp-json/taglock/v1/`
- Use proper HTTP methods (GET, POST, PUT, DELETE)
- Return consistent JSON responses
- Include proper error handling

## Questions?

If you have questions, feel free to:
- Open a discussion on GitHub
- Create an issue
- Reach out to the maintainers

## License

By contributing, you agree that your contributions will be licensed under the GPL v3 or later.

---

Thank you for contributing to TagLock! 🎉
