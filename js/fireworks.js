(function () {
  let running = true;
  if (sessionStorage.getItem('fireworksPlayed')) return;
  sessionStorage.setItem('fireworksPlayed', '1');
  const canvas = document.getElementById('fireworks-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W = window.innerWidth, H = window.innerHeight;
  canvas.width = W; canvas.height = H;
  window.addEventListener('resize', () => {
    W = window.innerWidth; H = window.innerHeight;
    canvas.width = W; canvas.height = H;
  });
  function randomColor() {
    const colors = ['#00ff9d', '#ff3e3e', '#fff', '#ffe066', '#00cfff', '#ff00c8'];
    return colors[Math.floor(Math.random() * colors.length)];
  }
  function Firework() {
    this.x = Math.random() * W;
    this.y = H;
    this.targetY = Math.random() * H * 0.5 + 80;
    this.color = randomColor();
    this.radius = 2 + Math.random() * 2;
    this.exploded = false;
    this.particles = [];
    this.vy = - (6 + Math.random() * 2);
    this.update = function () {
      if (!this.exploded) {
        this.y += this.vy;
        if (this.y <= this.targetY) {
          this.exploded = true;
          for (let i = 0; i < 32 + Math.random() * 24; i++) {
            const angle = (Math.PI * 2) * (i / 40);
            const speed = 2 + Math.random() * 2.5;
            this.particles.push({
              x: this.x, y: this.y, color: this.color,
              vx: Math.cos(angle) * speed,
              vy: Math.sin(angle) * speed,
              alpha: 1
            });
          }
        }
      } else {
        this.particles.forEach(p => {
          p.x += p.vx;
          p.y += p.vy;
          p.vy += 0.04;
          p.alpha -= 0.012 + Math.random() * 0.01;
        });
        this.particles = this.particles.filter(p => p.alpha > 0);
      }
    };
    this.draw = function () {
      if (!this.exploded) {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.fillStyle = this.color;
        ctx.shadowColor = this.color;
        ctx.shadowBlur = 12;
        ctx.fill();
        ctx.shadowBlur = 0;
      } else {
        this.particles.forEach(p => {
          ctx.globalAlpha = Math.max(0, p.alpha);
          ctx.beginPath();
          ctx.arc(p.x, p.y, 2, 0, Math.PI * 2);
          ctx.fillStyle = p.color;
          ctx.shadowColor = p.color;
          ctx.shadowBlur = 8;
          ctx.fill();
          ctx.shadowBlur = 0;
          ctx.globalAlpha = 1;
        });
      }
    };
  }
  let fireworks = [];
  function loop() {
    if (!running) return;
    ctx.clearRect(0, 0, W, H);
    if (Math.random() < 0.06) fireworks.push(new Firework());
    fireworks.forEach(fw => { fw.update(); fw.draw(); });
    fireworks = fireworks.filter(fw => !fw.exploded || fw.particles.length > 0);
    requestAnimationFrame(loop);
  }
  loop();
  window.addEventListener('beforeunload', () => { running = false; });
})(); 
