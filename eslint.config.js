import js from '@eslint/js';
import { defineConfig, globalIgnores } from 'eslint/config';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

export default defineConfig([
    globalIgnores(['public/', 'vendor/', 'node_modules/', 'storage/', 'bootstrap/cache/']),
    js.configs.recommended,
    {
        files: ['resources/js/**/*.js'],
        languageOptions: { globals: globals.browser },
    },
    {
        files: ['*.config.js'],
        languageOptions: { globals: globals.node },
    },
    // Formatting is Prettier's job; turn off ESLint rules that would conflict with it.
    prettier,
]);
