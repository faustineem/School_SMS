-- Adding foreign key constraints to enforce relationships
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_students_advisor_id` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `parent_student`
  ADD CONSTRAINT `fk_parent_student_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_parent_student_student_id` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Insert 5 teachers into users table
INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(7, 'Amani Juma Kibwana', 'amani.juma@school.com', '0751234001', '$2y$10$TJYhDxju7ZAAyLrYVGfM2eyGvsWdCbrFzi855InlKD/diqBs6xqSG', 'teacher', '2025-07-10 00:01:00'),
(8, 'Fatuma Mushi Omari', 'fatuma.mushi@school.com', '0751234002', '$2y$10$TJYhDxju7ZAAyLrYVGfM2eyGvsWdCbrFzi855InlKD/diqBs6xqSG', 'teacher', '2025-07-10 00:01:01'),
(9, 'Emmanuel John Msuya', 'emmanuel.msuya@school.com', '0751234003', '$2y$10$TJYhDxju7ZAAyLrYVGfM2eyGvsWdCbrFzi855InlKD/diqBs6xqSG', 'teacher', '2025-07-10 00:01:02'),
(10, 'Zainabu Ally Nyerere', 'zainabu.ally@school.com', '0751234004', '$2y$10$TJYhDxju7ZAAyLrYVGfM2eyGvsWdCbrFzi855InlKD/diqBs6xqSG', 'teacher', '2025-07-10 00:01:03'),
(11, 'Baraka Joseph Kileo', 'baraka.joseph@school.com', '0751234005', '$2y$10$TJYhDxju7ZAAyLrYVGfM2eyGvsWdCbrFzi855InlKD/diqBs6xqSG', 'teacher', '2025-07-10 00:01:04');

-- Insert 20 students into users table
INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(12, 'Neema Joseph Mwakyusa', 'neema.joseph@school.com', '0751234011', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:00'),
(13, 'Juma Ally Mushi', 'juma.ally@school.com', '0751234012', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:01'),
(14, 'Aisha Ramadhani Kweka', 'aisha.ramadhani@school.com', '0751234013', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:02'),
(15, 'David Eliud Mushi', 'david.eliud@school.com', '0751234014', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:03'),
(16, 'Fatuma Omari Nyerere', 'fatuma.omari@school.com', '0751234015', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:04'),
(17, 'Emmanuel Paulo Mollel', 'emmanuel.paulo@school.com', '0751234016', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:05'),
(18, 'Zainabu Juma Kibwana', 'zainabu.juma@school.com', '0751234017', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:06'),
(19, 'Baraka John Msuya', 'baraka.john@school.com', '0751234018', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:07'),
(20, 'Mariam Ally Kileo', 'mariam.ally@school.com', '0751234019', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:08'),
(21, 'Joseph Ramadhani Mushi', 'joseph.ramadhani@school.com', '0751234020', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:09'),
(22, 'Grace Paulo Nyerere', 'grace.paulo@school.com', '0751234021', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:10'),
(23, 'Hassan Omari Mollel', 'hassan.omari@school.com', '0751234022', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:11'),
(24, 'Rehema Juma Kweka', 'rehema.juma@school.com', '0751234023', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:12'),
(25, 'Elias Joseph Msuya', 'elias.joseph@school.com', '0751234024', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:13'),
(26, 'Amina Ally Kibwana', 'amina.ally@school.com', '0751234025', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:14'),
(27, 'Paulo Ramadhani Mushi', 'paulo.ramadhani@school.com', '0751234026', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:15'),
(28, 'Esther John Nyerere', 'esther.john@school.com', '0751234027', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:16'),
(29, 'Ismail Omari Kileo', 'ismail.omari@school.com', '0751234028', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:17'),
(30, 'Salome Juma Msuya', 'salome.juma@school.com', '0751234029', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:18'),
(31, 'Yusuph Ally Mollel', 'yusuph.ally@school.com', '0751234030', '$2y$10$dSk7MYr2lAtBGbeZi1crRu4J3r.sxt1jM9jRv3a7y1Mnu7JPgqUjW', 'student', '2025-07-10 00:02:19');

-- Insert 20 parents into users table
INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(32, 'Joseph Mwakyusa', 'joseph.mwakyusa@school.com', '0751234031', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:00'),
(33, 'Ally Mushi', 'ally.mushi@school.com', '0751234032', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:01'),
(34, 'Ramadhani Kweka', 'ramadhani.kweka@school.com', '0751234033', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:02'),
(35, 'Eliud Mushi', 'eliud.mushi@school.com', '0751234034', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:03'),
(36, 'Omari Nyerere', 'omari.nyerere@school.com', '0751234035', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:04'),
(37, 'Paulo Mollel', 'paulo.mollel@school.com', '0751234036', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:05'),
(38, 'Juma Kibwana', 'juma.kibwana@school.com', '0751234037', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:06'),
(39, 'John Msuya', 'john.msuya@school.com', '0751234038', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:07'),
(40, 'Ally Kileo', 'ally.kileo@school.com', '0751234039', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:08'),
(41, 'Ramadhani Mushi', 'ramadhani.mushi@school.com', '0751234040', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:09'),
(42, 'Paulo Nyerere', 'paulo.nyerere@school.com', '0751234041', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:10'),
(43, 'Omari Mollel', 'omari.mollel@school.com', '0751234042', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:11'),
(44, 'Juma Kweka', 'juma.kweka@school.com', '0751234043', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:12'),
(45, 'Joseph Msuya', 'joseph.msuya@school.com', '0751234044', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:13'),
(46, 'Ally Kibwana', 'ally.kibwana@school.com', '0751234045', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:14'),
(47, 'Ramadhani Mushi Sr', 'ramadhani.mushi.sr@school.com', '0751234046', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:15'),
(48, 'John Nyerere', 'john.nyerere@school.com', '0751234047', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:16'),
(49, 'Omari Kileo', 'omari.kileo@school.com', '0751234048', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:17'),
(50, 'Juma Msuya', 'juma.msuya@school.com', '0751234049', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:18'),
(51, 'Ally Mollel', 'ally.mollel@school.com', '0751234050', '$2y$10$T4Ygvg/lkesQsgCL0MYzWuD4ATwBpJC.C0WwvQ6aJgmX8bDzul8ju', 'parent', '2025-07-10 00:03:19');

-- Insert into students table with advisor_id assignments
INSERT INTO `students` (`student_id`, `user_id`, `admission_number`, `gender`, `date_of_birth`, `class`, `stream`, `address`, `advisor_id`, `profile_image`) VALUES
(1, 12, 'ADM001', 'female', '2010-03-15', 'Form 1', 'A', 'Arusha, Tanzania', 7, NULL),
(2, 13, 'ADM002', 'male', '2010-05-22', 'Form 1', 'A', 'Moshi, Tanzania', 7, NULL),
(3, 14, 'ADM003', 'female', '2010-07-10', 'Form 1', 'B', 'Dar es Salaam, Tanzania', 7, NULL),
(4, 15, 'ADM004', 'male', '2010-09-05', 'Form 1', 'B', 'Arusha, Tanzania', 7, NULL),
(5, 16, 'ADM005', 'female', '2010-11-20', 'Form 2', 'A', 'Moshi, Tanzania', 8, NULL),
(6, 17, 'ADM006', 'male', '2010-01-15', 'Form 2', 'A', 'Dar es Salaam, Tanzania', 8, NULL),
(7, 18, 'ADM007', 'female', '2010-04-12', 'Form 2', 'B', 'Arusha, Tanzania', 8, NULL),
(8, 19, 'ADM008', 'male', '2010-06-25', 'Form 2', 'B', 'Moshi, Tanzania', 8, NULL),
(9, 20, 'ADM009', 'female', '2010-08-30', 'Form 3', 'A', 'Dar es Salaam, Tanzania', 9, NULL),
(10, 21, 'ADM010', 'male', '2010-10-17', 'Form 3', 'A', 'Arusha, Tanzania', 9, NULL),
(11, 22, 'ADM011', 'female', '2010-12-05', 'Form 3', 'B', 'Moshi, Tanzania', 9, NULL),
(12, 23, 'ADM012', 'male', '2011-02-20', 'Form 3', 'B', 'Dar es Salaam, Tanzania', 9, NULL),
(13, 24, 'ADM013', 'female', '2011-04-15', 'Form 4', 'A', 'Arusha, Tanzania', 10, NULL),
(14, 25, 'ADM014', 'male', '2011-06-10', 'Form 4', 'A', 'Moshi, Tanzania', 10, NULL),
(15, 26, 'ADM015', 'female', '2011-08-25', 'Form 4', 'B', 'Dar es Salaam, Tanzania', 10, NULL),
(16, 27, 'ADM016', 'male', '2011-10-12', 'Form 4', 'B', 'Arusha, Tanzania', 10, NULL),
(17, 28, 'ADM017', 'female', '2011-12-01', 'Form 1', 'A', 'Moshi, Tanzania', 11, NULL),
(18, 29, 'ADM018', 'male', '2012-02-15', 'Form 1', 'A', 'Dar es Salaam, Tanzania', 11, NULL),
(19, 30, 'ADM019', 'female', '2012-04-20', 'Form 1', 'B', 'Arusha, Tanzania', 11, NULL),
(20, 31, 'ADM020', 'male', '2012-06-05', 'Form 1', 'B', 'Moshi, Tanzania', 11, NULL);

-- Insert into parent_student table to link students and parents
INSERT INTO `parent_student` (`id`, `parent_id`, `student_id`) VALUES
(1, 32, 12),
(2, 33, 13),
(3, 34, 14),
(4, 35, 15),
(5, 36, 16),
(6, 37, 17),
(7, 38, 18),
(8, 39, 19),
(9, 40, 20),
(10, 41, 21),
(11, 42, 22),
(12, 43, 23),
(13, 44, 24),
(14, 45, 25),
(15, 46, 26),
(16, 47, 27),
(17, 48, 28),
(18, 49, 29),
(19, 50, 30),
(20, 51, 31);

COMMIT;