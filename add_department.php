<?php
include 'db.php';

if(isset($_POST['save'])){
    
    $department = $_POST['department'];

    $sql = "INSERT INTO departments(department_name)
            VALUES('$department')";

    mysqli_query($conn, $sql);

    header("Location: admin.php?page=departments");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Department</title>

    <style>
        body{
            font-family:Arial;
            background:#f4f7fc;
            padding:40px;
        }

        .box{
            background:white;
            width:400px;
            padding:30px;
            border-radius:10px;
        }

        input{
            width:100%;
            padding:12px;
            margin-top:10px;
            margin-bottom:20px;
        }

        button{
            background:#2563eb;
            color:white;
            border:none;
            padding:12px 20px;
            cursor:pointer;
            border-radius:8px;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Add Department</h2>

    <form method="POST">

        <input type="text" name="department" placeholder="Department Name" required>

        <button type="submit" name="save">Save Department</button>

    </form>

</div>

</body>
</html>