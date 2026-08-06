<?php
$content = file_get_contents('C:\Users\RSUser\Desktop\PHP\sistema_preventiva\detalhe_preventiva.php');

// Find the duplicate block
$search = "<?php elseif (\$p['status'] === 'atendida' && \$atendimento && in_array(\$atendimento['status'], ['concluido', 'revisao'])): ?>";

$pos1 = strpos($content, $search);
if ($pos1 === false) {
    echo "Not found!\n";
    exit;
}

echo "Found first at: $pos1\n";

// Find the second occurrence (should be the duplicate)
$pos2 = strpos($content, $search, $pos1 + 1);
if ($pos2 === false) {
    echo "No second occurrence found\n";
    exit;
}

echo "Found second at: $pos2\n";

// Find the end of the second block (<?php endif; ?>)
$posEnd = strpos($content, "<?php endif; ?>", $pos2);
if ($posEnd === false) {
    echo "End not found\n";
    exit;
}

// Include the endif
$posEnd += strlen("<?php endif; ?>");

// Remove the duplicate block (from second occurrence to endif)
$newContent = substr($content, 0, $pos2) . substr($content, $posEnd);

// Also remove extra blank lines before the removed block
$newContent = preg_replace('/\n\s*\n\s*\n\s*<\?php endif;\?>/', "\n\n<?php endif; ?>", $newContent);

file_put_contents('C:\Users\RSUser\Desktop\PHP\sistema_preventiva\detalhe_preventiva.php', $newContent);
echo "Fixed!\n";