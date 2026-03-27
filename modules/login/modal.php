<?php

Class Login_modal{
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		
	}
	
	
		public function getAllRecords($tableName, $fields='*', $cond='')
    {
        $sql = "SELECT $fields FROM $tableName WHERE " . $cond;
        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            return null;
        }
        $rows = mysqli_fetch_assoc($result);
        return $rows;
    }

    public function getUserByLogin($username)
    {
        // Check which email column exists
        $emailCol = 'user_email';
        $check = mysqli_query($this->conn, "SHOW COLUMNS FROM users LIKE 'email'");
        if ($check && mysqli_num_rows($check) > 0) {
            // both columns exist, try user_email first, fall back to email
            $emailCol = 'user_email';
        }

        $safe = mysqli_real_escape_string($this->conn, $username);

        // Try user_email
        $sql = "SELECT * FROM users WHERE user_email = '$safe' LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        // Fall back to email column if it exists
        if ($check && mysqli_num_rows($check) > 0) {
            $sql2 = "SELECT * FROM users WHERE email = '$safe' LIMIT 1";
            $result2 = mysqli_query($this->conn, $sql2);
            if ($result2 && mysqli_num_rows($result2) > 0) {
                return mysqli_fetch_assoc($result2);
            }
        }

        return null;
    }
	
}