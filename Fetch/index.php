<?php
include("dbconnection.php");

// Kiểm tra kết nối database
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Xử lý xóa ảnh/video
if (isset($_GET['delete'])) {
    $imageId = intval($_GET['delete']);

    // Lấy tên file từ database
    $result = mysqli_query($con, "SELECT file_name FROM images WHERE id = $imageId");
    if ($row = mysqli_fetch_assoc($result)) {
        $filePath = "Images/" . $row['file_name'];

        // Xóa file khỏi thư mục
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Xóa dữ liệu trong database
        mysqli_query($con, "DELETE FROM images WHERE id = $imageId");

        // Chuyển hướng lại trang để tránh lỗi refresh
        header("Location: index.php");
        exit();
    } else {
        echo "File not found!";
    }
}

// Xử lý upload ảnh/video
if (isset($_POST['submit'])) {
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $fileName = mysqli_real_escape_string($con, $_FILES['media']['name']);
        $fileTmpName = $_FILES['media']['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];
        $uploadDir = 'Images/';

        // Kiểm tra và tạo thư mục nếu chưa có
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Kiểm tra loại file hợp lệ
        if (!in_array($fileExt, $allowedExt)) {
            echo "Only JPG, PNG, GIF, MP4, AVI, and MOV files are allowed.";
            exit;
        }

        // Tạo tên file mới để tránh trùng lặp
        $newFileName = uniqid() . '.' . $fileExt;
        $filePath = $uploadDir . $newFileName;

        // Thực hiện upload file
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Lưu vào database kèm theo ngày tháng upload
            $query = mysqli_query($con, "INSERT INTO images (file_name, time) VALUES ('$newFileName', NOW())");
            if ($query) {
                // Chuyển hướng để tránh lỗi reload form
                header("Location: index.php");
                exit();
            } else {
                echo "Failed to save file info to database.";
            }
        } else {
            echo "Failed to upload file.";
        }
    } else {
        echo "No file selected or upload error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Media</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
            background-color: #f5f5f5;
        }

        header {
            width: 100%;
            background-color: #4CAF50;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 1rem;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .upload-form input[type="file"], .upload-form button {
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .upload-form button {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .upload-form button:hover {
            background-color: #45a049;
        }

        .media-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .media-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            background-color: #f5f5f5;
        }

        .media-item img, .media-item video {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .media-item img:hover {
            transform: scale(1.2);
        }

        .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: red;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.2rem 0.5rem;
            cursor: pointer;
        }

        .upload-date {
            color: grey;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            margin-top: 0.2rem;
        }

        .zoom-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: none; /* Mặc định ẩn */
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .zoom-overlay img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }

        .zoom-overlay .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            padding: 0.5rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <h1>Upload Media</h1>
    </header>

    <div class="container">
        <form class="upload-form" method="POST" enctype="multipart/form-data">
            <input type="file" name="media" accept="image/*,video/*" required>
            <button type="submit" name="submit">Upload</button>
        </form>

        <div class="media-list">
            <?php
            $res = mysqli_query($con, "SELECT * FROM images ORDER BY id DESC");
            while ($row = mysqli_fetch_assoc($res)) {
                
                echo '<div class="media-item">';
                $fileExt = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));

                if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo '<img src="Images/' . htmlspecialchars($row['file_name']) . '" onclick="zoomImage(this.src)" />';
                } elseif (in_array($fileExt, ['mp4', 'avi', 'mov'])) {
                    echo '<video src="Images/' . htmlspecialchars($row['file_name']) . '" controls></video>';
                }
                echo '<div class="upload-date">' . date("d/m/Y", strtotime($row['time'])) . '</div>';
                // Hiển thị ngày tháng upload dưới ảnh/video
             

                echo '<a href="index.php?delete=' . $row['id'] . '" onclick="return confirm(\'Are you sure?\');">
                        <button class="delete-btn">X</button></a>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script>
        function zoomImage(src) {
            var overlay = document.createElement("div");
            overlay.classList.add("zoom-overlay");

            var img = document.createElement("img");
            img.src = src;

            var closeBtn = document.createElement("button");
            closeBtn.classList.add("close-btn");
            closeBtn.textContent = "X";
            closeBtn.onclick = function() {
                overlay.style.display = "none";
            };

            overlay.appendChild(img);
            overlay.appendChild(closeBtn);

            document.body.appendChild(overlay);

            overlay.style.display = "flex";
        }
    </script>
</body>
</html>
