<!DOCTYPE html>
<html>
    <head>
        <?php include "_head.php" ?>
    </head>
    <body class="">
        <div>
            <header>            
                <nav>
                    <ul>
                        <li><a href="/about">About</a></li>
                        <li><a href="/Contact">Contact</a></li>
                    </ul>
                </nav>
            </header>

            <section id="content">
                <?php include $viewFile ?>
            </section>

        </div>
    </body>
</html>
