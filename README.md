# Editor de Láminas Fotográficas - VyR Producciones

## Estado actual:
- [x] Sistema de login (admin/familia)
- [x] Base de datos con stored procedures
- [x] Editor básico de lienzos
- [x] CRUD de eventos (admin)
- [x] CRUD de familias (admin)  
- [x] Integración completa login-editor
- [ ] Exportación masiva de lienzos
- [ ] Editor de Lienzo como adjunto

## Archivos faltantes:
No faltan solo hay que optimizarlo

## Problemas conocidos:
1. Conflicto campos 'email' vs 'correo' en SPs
2. Falta manejo de sesiones en editor
3. No hay validación de estado de usuario en editor

## Resumen:
Diseño de un editor de lienzo fotografico.
Roles: Admin y Familia 
-El Admin:
1.- Inicia sesion y puede gestionar eventos, es decir un crud
2.- Cuando lista los eventos, en registro hay una opcion para ver la lista de familias registradas en dicho evento y tambien otra para exportar todos los lienzos familia por familia a imagen.
3.- Puede gestionar las familias (otro crud)
4.- Desde la lista de familias hay una opcion que le permite acceder al lienzo editado por cada familia para poner revisar y hacer algun ajuste si lo considera necesario para luego exportarlo como imagen.
-El usuario Familia:
1.- Inicia sesion y si aun no se ha registrado puede registrarse. Si en caso se olvida su comtraseña podra recuperarla.
2.- Luego de iniciar sesion solo puede acceder al Editor de Lienzo para seleccionar una plantillas y colocar las potos que cosidera, luego le da guardar y sale de la aplicacion.
-El lienzo:
Tiene una lista de plantillas donde cada una tiene una distribucion diferente de recuadros para las imagenes
Existira una linea de separacion entre imagenes de un gross de 2mm
Existira una linea discontinua de color gris de 2mm de grosor a la mitad del lienzo de forma vertical, esto servira solo de guia para que se sepa que alli se hara un dobles
Cada recuadro dellienzo permite arrstrar la imagen sobre esta y al soltarla se abrira alli, tambien hay un icono de carpeta para que al hacer clic permita abrir una imagen en dicho recuadro, tambien tiene los simbolos + / - que permiten incrementar el zoom y reducir el zoom
Las imagenes se pueden mover dentro de cada recuadro.
El usuario familia cada vez que inici sesion pued acceder al Editor de Lienzo tal como lo dejo su viista anterio al guardarlo y continuar su edicion
Cada vez que guarda, el sistema le indicara que se ha guardado sus cambio correctamente.
Cuando accede el Admin desde la pagina de Familias al lienzo de la familia tendra la opcion de guardar con el id identificador de la familia. Tambien tiene la opcion de Exportar para generar la imagen  25.4 cm x 60.5 cm pues siempre sera este el tamaño final si importar la plantilla seleccionada

 
