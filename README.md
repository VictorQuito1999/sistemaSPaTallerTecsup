# vue
amdin crdenciales=admin@petspa.local / PetSpa#Admin1

Cómo funcionaría

    El cliente en el formulario solo elige el tamaño (Pequeño, Mediano, Grande, Gigante).

    En tu backend, al guardar la mascota, puedes asignar un peso referencial automático según el tamaño.

    Ejemplo de mapeo:

        Pequeño → 5 kg

        Mediano → 15 kg

        Grande → 30 kg

        Gigante → 45 kg

Ventajas

    El cliente no se complica ingresando datos exactos.

    Tu algoritmo de duración ya puede trabajar con un número (aunque sea aproximado).

    Más adelante, recepción o el groomer pueden actualizar el peso real cuando midan la mascota.
