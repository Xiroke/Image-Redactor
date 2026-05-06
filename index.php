<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Photo edit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-container">
        <div id="layers">
        </div>
        <canvas id="canvas" width="1600" height="900"></canvas>
        <div class="tools">
            <div id="image-fields">
                <label>
                    X:
                    <input type="number" id="posX" class="form-control" placeholder="X">
                </label>
                <label>
                    Y:
                    <input type="number" id="posY" class="form-control" placeholder="Y">
                </label>
                <label>
                    Ширина:
                <input type="number" id="width" class="form-control" placeholder="Ширина">
                </label>
                <label>
                    Высота:
                    <input type="number" id="height" class="form-control" placeholder="Высота">
                </label>
                <button class="btn btn-primary" onclick="apply()">Применить</button>
                <button class="btn btn-danger" onclick="destroy()">Удалить</button>
            </div>
            <div class="load_images">
                <button class="btn btn-primary" onclick="exportProject()">Экспортировать</button>
                <button class="btn btn-primary" onclick="addSquare()">Квадрат</button>
                <button class="btn btn-primary" onclick="addCircle()">Круг</button>
                <button class="btn btn-primary" onclick="addLine()">Линия</button>
                <select class="form-select" id="filter-input">
                    <option value="">Выберите фильтр</option>
                    <option value="sepia(80%)">Сепия</option>
                    <option value="grayscale(80%)">Черно-Белое</option>
                    <option value="saturate(80%)">Насыщенность</option>
                    <option value="opacity(80%)">Прозрачность</option>
                </select>
                <input type="file" class="form-control" id="file-input">
            </div>
        </div>
    </div>
    <script>
        const layers = document.getElementById('layers')
        const wrapper = document.createElement('div')

        const canvas = document.getElementById('canvas')
        const filterInput = document.getElementById('filter-input')
        const ctx = canvas.getContext('2d')
        let canvasFilter;
        let images = []
        let selected = -1

        // отрисовка изображений на canvas
        const draw = () => {
            layers.innerHTML = ''
            wrapper.className = 'itemWrapper'
            wrapper.innerHTML = 'Элемент - Слой'
            layers.appendChild(wrapper)

            ctx.fillStyle = 'white'
            ctx.fillRect(0, 0, 1600, 900)
            images.sort((a, b) => a.zIndex - b.zIndex)
            images.forEach((shape, i) => {
                if (canvasFilter) {
                    ctx.filter = canvasFilter
                }

                ctx.fillStyle = 'black'
                if (shape.type === 'image') {
                    ctx.drawImage(shape.img, shape.x, shape.y, shape.w, shape.h)
                }
                if (shape.type === 'square') {
                    ctx.fillRect(shape.x, shape.y, shape.w, shape.h)
                }
                if (shape.type === 'line') {
                    ctx.fillRect(shape.x, shape.y, shape.w, shape.h)
                }
                if (shape.type === 'circle') {
                    ctx.beginPath()
                    ctx.arc(shape.x + shape.w / 2, shape.y + shape.w / 2, shape.w / 2, 0, Math.PI * 2)
                    ctx.fillStyle = 'black'
                    ctx.fill()
                }

                if (i === selected) {
                    ctx.strokeStyle = 'red'
                    ctx.lineWidth = 3
                    ctx.strokeRect(shape.x, shape.y, shape.w, shape.h)
                }

                const wrapper = document.createElement('div')
                wrapper.className = 'itemWrapper'

                const button = document.createElement('button');
                button.innerHTML = shape.title
                button.className = 'btn btn-outline-primary'
                button.addEventListener('click', (event) => {
                    setSelected(event, i);
                });

                const input = document.createElement('input')
                input.className = 'form-control'
                input.value = shape.zIndex
                input.addEventListener('change', (e) => {
                    setZIndex(i, e.target.value)
                })

                wrapper.appendChild(button)
                wrapper.appendChild(input)
                layers.appendChild(wrapper)
            })
        }

        // установка значений в input
        const updateInputs = () => {
            if (selected >= 0) {
                const img = images[selected]
                document.getElementById('image-fields').style.display = 'flex'
                document.getElementById('posX').value = img.x
                document.getElementById('posY').value = img.y
                document.getElementById('width').value = img.w
                document.getElementById('height').value = img.h
            }
        }

        // Применение переноса и перемещения
        const apply = () => {
            if (selected < 0) return
            const img = images[selected]
            img.x = parseInt(document.getElementById('posX').value) || img.x
            img.y = parseInt(document.getElementById('posY').value) || img.y
            img.w = parseInt(document.getElementById('width').value) || img.w
            img.h = parseInt(document.getElementById('height').value) || img.h
            draw()
        }

        // удаление элемента
        const destroy = () => {
            images.splice(selected, 1)
            draw()
        }

        // Экспортировние фотограии
        const exportProject = () => {
            canvas.toBlob(blob => {
                const formData = new FormData()
                formData.append('image', blob, 'result.png')
                fetch('/save-image.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json()).then(data => {
                    const a = document.createElement('a')
                    a.href = data.url
                    a.download = 'result.png'
                    a.click()
                })
            })
        }

        // Добавление изоюражения
        const addImage = (file) => {
            const reader = new FileReader()
            reader.onload = (e) => {
                const img = new Image()
                img.onload = () => {
                    const w = 300
                    const h = (img.height / img.width) * w
                    images.push({ title: 'Обьект '+images.length, zIndex: images.length, type: 'image', img, x: 50, y: 50, w, h })
                    selected = images.length - 1
                    updateInputs()
                    draw()
                }
                img.src = e.target.result
            }
            reader.readAsDataURL(file)
        }

        // добавление круга
        const addCircle = () => {
            images.push({ title: 'Обьект '+images.length, zIndex: images.length, type: 'circle', x: 50, y: 50, w: 300, h: 300 })
            draw()
        }
        // добавление квадрата
        const addSquare = () => {
            images.push({ title: 'Обьект '+images.length, zIndex: images.length, type: 'square', x: 50, y: 50, w: 300, h: 300 })
            draw()
        }
        // добавление линии
        const addLine = () => {
            images.push({ title: 'Обьект '+images.length, zIndex: images.length, type: 'line', x: 50, y: 50, w: 300, h: 10 })
            draw()
        }

        // выбрать фигуру
        window.setSelected = (e, index) => {
            selected = index
            draw()
        }
        // изменить слой фигуры
        window.setZIndex = (index, zIndex) => {
            images[index].zIndex = zIndex
            draw()
        }

        // Получение фотографий загруженных через input
        document.getElementById('file-input').onchange = (e) => {
            Array.from(e.target.files).forEach(f => addImage(f))
            e.target.value = ''
        }

        let onDragStartMouseX;
        let onDragStartMouseY;
        let isDrag = false;
        const rect = canvas.getBoundingClientRect()

        // Обработка клика
        canvas.addEventListener('mousedown', (e) => {
            isDrag = true
            const scaleX = canvas.width / rect.width
            const scaleY = canvas.height / rect.height
            const mouseX = (e.clientX - rect.left) * scaleX
            const mouseY = (e.clientY - rect.top) * scaleY


            let hit = false
            for (let i = images.length - 1; i > -1; i--) {
                const img = images[i]
                if (mouseX >= img.x && mouseX <= img.x + img.w && mouseY >= img.y && mouseY <= img.y + img.h) {
                    selected = i
                    onDragStartMouseX = img.x - mouseX
                    onDragStartMouseY = img.y - mouseY
                    console.log(onDragStartMouseX, onDragStartMouseY)
                    hit = true
                    break
                }
            }

            if (!hit) {
                selected = -1
                document.getElementById('image-fields').style.display = 'none'
            }

            updateInputs()
            draw()
        })

        canvas.addEventListener('mousemove', (e) => {
            if (!isDrag || selected === -1) return

            images[selected].x = e.clientX - rect.left + onDragStartMouseX
            images[selected].y = e.clientY - rect.top + onDragStartMouseY
            updateInputs()
            draw()
        })

        canvas.addEventListener('mouseup', (e) => {
            isDrag = false;
        });

        filterInput.addEventListener('change', (e) => {
            if (e.target.value) {
                canvasFilter = e.target.value
            }
        })
    </script>
</body>
</html>
