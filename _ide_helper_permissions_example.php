<?php

/**
 * UnPerm IDE Helper - EXAMPLE
 * 
 * This file provides autocomplete support for IDE.
 * Generated: 2025-10-03 (Example)
 * 
 * Этот файл автоматически генерируется командой:
 * php artisan unperm:generate-ide-helper
 */

namespace DFiks\UnPerm\IdeHelper {

    /**
     * IDE Helper for HasPermissions trait
     * 
     * This class provides autocomplete for permission methods.
     * All methods work through bitmask operations for maximum performance.
     */
    class HasPermissionsIdeHelper
    {
        // =============== ACTIONS ===============

        /**
         * View users
         * 
         * @return $this
         */
        public function assignAction_users_view() {}

        /**
         * View users
         * 
         * @return $this
         */
        public function removeAction_users_view() {}

        /**
         * Check: View users
         * 
         * @return bool
         */
        public function hasAction_users_view() {}

        /**
         * Create posts
         * 
         * @return $this
         */
        public function assignAction_posts_create() {}

        /**
         * Create posts
         * 
         * @return $this
         */
        public function removeAction_posts_create() {}

        /**
         * Check: Create posts
         * 
         * @return bool
         */
        public function hasAction_posts_create() {}

        // =============== ROLES ===============

        /**
         * Administrator - Full system access
         * 
         * @return $this
         */
        public function assignRole_admin() {}

        /**
         * Administrator - Full system access
         * 
         * @return $this
         */
        public function removeRole_admin() {}

        /**
         * Check: Administrator - Full system access
         * 
         * @return bool
         */
        public function hasRole_admin() {}

        // =============== GROUPS ===============

        /**
         * Content Team - Content management team
         * 
         * @return $this
         */
        public function assignGroup_content_team() {}

        /**
         * Content Team - Content management team
         * 
         * @return $this
         */
        public function removeGroup_content_team() {}

        /**
         * Check: Content Team - Content management team
         * 
         * @return bool
         */
        public function hasGroup_content_team() {}
    }
}

namespace {

    /**
     * Permission Constants
     * Use these constants instead of strings for better IDE support
     */
    class UnPermActions
    {
        public const USERS_VIEW = 'users.view';
        public const USERS_CREATE = 'users.create';
        public const POSTS_VIEW = 'posts.view';
        public const POSTS_CREATE = 'posts.create';
        public const POSTS_EDIT = 'posts.edit';
    }

    class UnPermRoles
    {
        public const ADMIN = 'admin';
        public const EDITOR = 'editor';
        public const MODERATOR = 'moderator';
    }

    class UnPermGroups
    {
        public const CONTENT_TEAM = 'content-team';
        public const MANAGEMENT = 'management';
    }
}

