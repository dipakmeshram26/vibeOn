function loadMessages() {
    fetch("load_messages.php?receiver_id=<?= $chat_with ?>")
        .then(r => r.json())  // ab JSON parse hoga
        .then(messages => {
            let chatHTML = "";
            messages.forEach(msg => {
                chatHTML += `
                    <div class="message ${msg.type}">
                        <span class="sender">${msg.sender}:</span> ${msg.text}
                    </div>
                `;
            });
            document.getElementById("chatBox").innerHTML = chatHTML;
            document.getElementById("chatBox").scrollTop = document.getElementById("chatBox").scrollHeight;
        });
}
