document.getElementById('addTaskButton').addEventListener('click', function() {
    const taskInput = document.getElementById('task-input'); // Memperbaiki ID input
    const taskText = taskInput.value;

    if (taskText === "") return; // Tidak menambah task kosong

    const taskList = document.getElementById('task-list'); // Memperbaiki ID daftar tugas
    
    // Buat elemen <li>
    const newTask = document.createElement('li');
    newTask.innerHTML = `
        <span>${taskText}</span>
        <div>
            <button class="check-button">✔</button>
            <button class="delete-button">Delete</button>
        </div>
    `;
    
    // Event tombol centang
    const checkButton = newTask.querySelector('.check-button');
    checkButton.addEventListener('click', function() {
        if (newTask.classList.contains('completed')) {
            newTask.classList.remove('completed');
            checkButton.textContent = "✔";
        } else {
            newTask.classList.add('completed');
            checkButton.textContent = "✖";
        }
    });

    // Event tombol hapus
    const deleteButton = newTask.querySelector('.delete-button');
    deleteButton.addEventListener('click', function() {
        taskList.removeChild(newTask);
    });

    // Tambahkan task ke daftar
    taskList.appendChild(newTask);
    taskInput.value = ""; // Reset input setelah ditambahkan
});
