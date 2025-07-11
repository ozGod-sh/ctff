tailwind.config={theme:{extend:{colors:{primary:'#00ff9d',secondary:'#ff3e3e'},borderRadius:{'none':'0px','sm':'4px',DEFAULT:'8px','md':'12px','lg':'16px','xl':'20px','2xl':'24px','3xl':'32px','full':'9999px','button':'8px'}}}};

(function() {
document.addEventListener('DOMContentLoaded', function() {
const canvas = document.getElementById('matrixCanvas');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;
const fontSize = 12;
const columns = Math.floor(canvas.width / fontSize);
const drops = [];
for (let i = 0; i < columns; i++) {
drops[i] = Math.floor(Math.random() * canvas.height);
}
const matrix = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789@#$%^&*()*&^%+-/~{[|`]}`";
function draw() {
ctx.fillStyle = 'rgba(10, 14, 23, 0.05)';
ctx.fillRect(0, 0, canvas.width, canvas.height);
ctx.fillStyle = '#00ff9d';
ctx.font = fontSize + 'px monospace';
for (let i = 0; i < drops.length; i++) {
const text = matrix[Math.floor(Math.random() * matrix.length)];
ctx.fillText(text, i * fontSize, drops[i] * fontSize);
if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
drops[i] = 0;
}
drops[i]++;
}
}
setInterval(draw, 33);
window.addEventListener('resize', function() {
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;
});
});
})();

(function() {
document.addEventListener('DOMContentLoaded', function() {
  const countdownElement = document.getElementById('countdown');
  
  function updateCountdown() {
    if (!countdownElement) return;

    const COUNTDOWN_KEY = 'ctf_countdown_end_time';
    let endTime = localStorage.getItem(COUNTDOWN_KEY);

    // Si aucune heure de fin n'est stockée, ou si l'heure stockée est passée, définissez un nouveau compte à rebours de 24 heures.
    if (!endTime || new Date().getTime() > parseInt(endTime)) {
      endTime = new Date().getTime() + 24 * 60 * 60 * 1000;
      localStorage.setItem(COUNTDOWN_KEY, endTime);
    }

    const end = new Date(parseInt(endTime));
    const now = new Date();
    const diff = end - now;

    if (diff <= 0) {
      countdownElement.innerHTML = `00:00:00`;
      return;
    }

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    countdownElement.innerHTML = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);
});
})();

(function() {
document.addEventListener('DOMContentLoaded', function() {
const particlesContainer = document.getElementById('particles');
const particleCount = 50;
for (let i = 0; i < particleCount; i++) {
const particle = document.createElement('div');
particle.classList.add('particle');
const size = Math.random() * 3 + 1;
particle.style.width = `${size}px`;
particle.style.height = `${size}px`;
const posX = Math.random() * 100;
const posY = Math.random() * 100;
particle.style.left = `${posX}%`;
particle.style.top = `${posY}%`;
const duration = Math.random() * 50 + 10;
const delay = Math.random() * 5;
particle.style.animation = `float ${duration}s ${delay}s infinite linear`;
particlesContainer.appendChild(particle);
}
const style = document.createElement('style');
style.textContent = `
@keyframes float {
0% {
transform: translate(0, 0);
opacity: 0;
}
10% {
opacity: 0.5;
}
90% {
opacity: 0.5;
}
100% {
transform: translate(${Math.random() > 0.5 ? '+' : '-'}${Math.random() * 100 + 50}px, ${Math.random() > 0.5 ? '+' : '-'}${Math.random() * 100 + 50}px);
opacity: 0;
}
}
`;
document.head.appendChild(style);
});
})();

(function() {
document.addEventListener('DOMContentLoaded', function() {
const difficultyRange = document.getElementById('difficultyRange');
const difficultyValue = document.getElementById('difficultyValue');
const volumeRange = document.getElementById('volumeRange');
const volumeIcon = document.getElementById('volumeIcon');
const soundToggle = document.getElementById('soundToggle');
const acceptMissionBtn = document.getElementById('acceptMission');
const viewClassifiedBtn = document.getElementById('viewClassified');

// Debug: Vérifier si les éléments sont trouvés
console.log('acceptMissionBtn:', acceptMissionBtn);
console.log('missionModal:', document.getElementById('mission-confirmation-modal'));

if (difficultyRange && difficultyValue) {
  let value = parseInt(difficultyRange.value);
  let difficultyText = 'STANDARD';
  if (value === 1) {
    difficultyText = 'DÉBUTANT';
  } else if (value === 3) {
    difficultyText = 'EXPERT';
  }
  difficultyValue.textContent = difficultyText;
  difficultyRange.addEventListener('input', function() {
    let value = parseInt(this.value);
    let difficultyText = 'STANDARD';
    if (value === 1) {
      difficultyText = 'DÉBUTANT';
    } else if (value === 3) {
      difficultyText = 'EXPERT';
    }
    difficultyValue.textContent = difficultyText;
  });
}
if (volumeRange && volumeIcon) {
  volumeRange.addEventListener('input', function() {
    const value = parseInt(this.value);
    if (value === 0) {
      volumeIcon.className = 'ri-volume-mute-line text-primary';
    } else if (value < 50) {
      volumeIcon.className = 'ri-volume-down-line text-primary';
    } else {
      volumeIcon.className = 'ri-volume-up-line text-primary';
    }
  });
}
if (soundToggle && volumeRange && volumeIcon) {
  soundToggle.addEventListener('change', function() {
    volumeRange.disabled = !this.checked;
    if (!this.checked) {
      volumeIcon.className = 'ri-volume-mute-line text-gray-500';
    } else {
      const value = parseInt(volumeRange.value);
      if (value === 0) {
        volumeIcon.className = 'ri-volume-mute-line text-primary';
      } else if (value < 50) {
        volumeIcon.className = 'ri-volume-down-line text-primary';
      } else {
        volumeIcon.className = 'ri-volume-up-line text-primary';
      }
    }
  });
}

// Gestion du modal de confirmation de mission
const missionModal = document.getElementById('mission-confirmation-modal');
if (acceptMissionBtn && missionModal) {
  console.log('Modal trouvé, ajout des event listeners');
  const closeModalBtn = document.getElementById('modal-close-button');
  const confirmBtn = document.getElementById('modal-confirm-button');
  const cancelBtn = document.getElementById('modal-cancel-button');

  acceptMissionBtn.addEventListener('click', () => {
    console.log('Bouton acceptMission cliqué');
    missionModal.classList.remove('hidden');
  });

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
      missionModal.classList.add('hidden');
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => {
      missionModal.classList.add('hidden');
    });
  }

  if (confirmBtn) {
    confirmBtn.addEventListener('click', () => {
      window.location.href = 'login.html';
    });
  }
} else {
  console.log('Modal ou bouton non trouvé:', {
    acceptMissionBtn: !!acceptMissionBtn,
    missionModal: !!missionModal
  });
}

if (viewClassifiedBtn) {
  viewClassifiedBtn.addEventListener('click', function() {
    // Code pour le bouton dossier classifié
    const classifiedDossierModal = document.getElementById('classified-dossier-modal');
    const classifiedDossierClose = document.getElementById('classified-dossier-close');
    
    if (classifiedDossierModal) {
      classifiedDossierModal.classList.remove('hidden');
    }
    
    if (classifiedDossierClose) {
      classifiedDossierClose.addEventListener('click', function() {
        classifiedDossierModal.classList.add('hidden');
      });
    }
    
    // Fermer le modal en cliquant à l'extérieur
    classifiedDossierModal.addEventListener('click', function(e) {
      if (e.target === classifiedDossierModal) {
        classifiedDossierModal.classList.add('hidden');
      }
    });
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !classifiedDossierModal.classList.contains('hidden')) {
        classifiedDossierModal.classList.add('hidden');
      }
    });
  });
}
});
})();

document.addEventListener('DOMContentLoaded', function () {
  const preloader = document.getElementById('preloader');
  if (!preloader) return;
  const terminal = document.getElementById('preloader-terminal-lines');
  const lines = [
    '> Connexion au satellite sécurisé [OK]',
    '> Initialisation du protocole PHÉNIX',
    '> Double déchiffrement en cours...',
    '> Synchronisation des paquets ∆-02... [Terminé]'
  ];
  const finalLine = '> Connexion sécurisée. Accès autorisé.';

  let speedFactor = 1;
  if (navigator.connection && navigator.connection.effectiveType) {
    const type = navigator.connection.effectiveType;
    if (type === '4g') speedFactor = 1;
    else if (type === '3g') speedFactor = 1.2;
    else if (type === '2g' || type === 'slow-2g') speedFactor = 1.5;
    else speedFactor = 1.1;
  }

  const totalDuration = 2000; // 2 secondes max pour tout le préloader
  const baseTypingSpeed = Math.max(8, Math.floor(totalDuration / (lines.join('').length + finalLine.length + 10)));
  const baseLineDelay = 0;
  let currentLine = 0;
  let startTime = Date.now();

  function typeLine(line, cb) {
    let i = 0;
    const span = document.createElement('span');
    span.className = 'preloader-terminal-line';
    terminal.appendChild(span);
    function typeChar() {
      if (i < line.length) {
        span.innerHTML =
          span.textContent.slice(0, i) +
          '<span class="preloader-terminal-cursor-square">' + line[i] + '</span>';
        i++;
        setTimeout(typeChar, baseTypingSpeed);
      } else {
        span.textContent = line;
        cb && cb();
      }
    }
    typeChar();
  }

  function showLines() {
    if (currentLine < lines.length) {
      typeLine(lines[currentLine], function () {
        currentLine++;
        setTimeout(showLines, baseLineDelay);
      });
    } else {
      typeLine(finalLine, function () {
        const last = document.createElement('span');
        last.className = 'preloader-terminal-line';
        last.innerHTML = '<span class="preloader-terminal-cursor">_</span>';
        terminal.appendChild(last);
        setTimeout(hidePreloader, 600); // délai court avant disparition
      });
    }
  }

  function hidePreloader() {
    preloader.classList.add('preloader-fadeout');
    document.body.style.overflow = '';
    setTimeout(() => {
      preloader.remove();
    }, 700);
  }

  document.body.style.overflow = 'hidden';
  setTimeout(() => {
    showLines();
  }, 80);

  preloader.addEventListener('transitionend', function () {
    document.body.style.overflow = '';
  });

  const archiveBtn = document.getElementById('archiveBtn');
  const confidentialBlock = document.getElementById('confidentialBlock');
  const archiveCard = document.getElementById('archiveCard');
  let isArchived = false;

  if (archiveBtn && confidentialBlock && archiveCard) {
    archiveBtn.addEventListener('click', function () {
      if (isArchived) return;
      confidentialBlock.classList.add('archived');
      setTimeout(() => {
        confidentialBlock.style.display = 'none';
        archiveCard.style.display = 'flex';
        archiveCard.classList.add('archive-card-blink');
        isArchived = true;
      }, 700);
    });
    archiveCard.addEventListener('click', function () {
      if (!isArchived) return;
      archiveCard.classList.remove('archive-card-blink');
      archiveCard.style.display = 'none';
      confidentialBlock.style.display = '';
      setTimeout(() => {
        confidentialBlock.classList.remove('archived');
        isArchived = false;
      }, 30);
    });
  }

  const eraseBtn = document.getElementById('effacerBtn');
  if (eraseBtn && confidentialBlock) {
    eraseBtn.addEventListener('click', function () {
      const commanderMsg = document.getElementById('commanderMessage');
      if (!commanderMsg) return;
      const lines = Array.from(commanderMsg.querySelectorAll('p'));
      function eraseLine(i) {
        if (i < 0) {
          confidentialBlock.classList.add('confidential-fadeout');
          setTimeout(() => { confidentialBlock.style.display = 'none'; }, 750);
          return;
        }
        lines[i].classList.add('confidential-fadeout');
        setTimeout(() => {
          lines[i].style.display = 'none';
          eraseLine(i - 1);
        }, 540);
      }
      eraseLine(lines.length - 1);
    });
  }

  const classifiedSheet = document.getElementById('classifiedSheet');
  const viewClassifiedBtn2 = document.getElementById('viewClassified');
  const closeClassifiedSheet = document.getElementById('closeClassifiedSheet');
  if (classifiedSheet && viewClassifiedBtn2) {
    viewClassifiedBtn2.addEventListener('click', function() {
      classifiedSheet.classList.add('open');
      setTimeout(() => {
        classifiedSheet.scrollIntoView({behavior: 'smooth', block: 'center'});
      }, 80);
    });
  }
  if (classifiedSheet && closeClassifiedSheet) {
    closeClassifiedSheet.addEventListener('click', function() {
      classifiedSheet.classList.remove('open');
    });
    window.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && classifiedSheet.classList.contains('open')) {
        classifiedSheet.classList.remove('open');
      }
    });
    classifiedSheet.addEventListener('click', function(e) {
      if (e.target === classifiedSheet) {
        classifiedSheet.classList.remove('open');
      }
    });
  }
});

// The active agent count functions have been removed as they depended on the backend.
