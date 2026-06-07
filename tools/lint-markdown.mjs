import { mkdtemp, readFile, readdir, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { spawnSync } from "node:child_process";
import prettier from "prettier";

const rootDir = process.cwd();
const markdownFiles = [
    path.join(rootDir, "README.md"),
    path.join(rootDir, "AGENTS.md"),
    ...(await collectMarkdownFiles(path.join(rootDir, "docs"))),
];

let hasFailures = false;

for (const file of markdownFiles) {
    const source = await readFile(file, "utf8");

    let formatted;
    try {
        const options = {
            ...((await prettier.resolveConfig(file, { editorconfig: true })) ??
                {}),
            filepath: file,
        };

        formatted = await prettier.format(source, options);
    } catch (error) {
        hasFailures = true;
        console.error(`\n${path.relative(rootDir, file)}`);
        console.error(formatPrettierError(error));
        continue;
    }

    if (formatted === source) {
        continue;
    }

    hasFailures = true;
    const classification = classifyMarkdownDiff(source, formatted);
    if (classification !== null) {
        console.error(`\n[hint] ${classification}`);
    }
    await printDiff(file, source, formatted);
}

if (hasFailures) {
    process.exitCode = 1;
}

async function collectMarkdownFiles(directory) {
    const entries = await readdir(directory, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const resolvedPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            files.push(...(await collectMarkdownFiles(resolvedPath)));
            continue;
        }

        if (entry.isFile() && entry.name.endsWith(".md")) {
            files.push(resolvedPath);
        }
    }

    return files;
}

function formatPrettierError(error) {
    if (error instanceof Error) {
        return error.message;
    }

    return String(error);
}

async function printDiff(file, source, formatted) {
    const tempDir = await mkdtemp(path.join(tmpdir(), "lint-markdown-"));
    const formattedFile = path.join(tempDir, path.basename(file));

    await writeFile(formattedFile, formatted, "utf8");

    console.error(`\n${path.relative(rootDir, file)}`);

    const diffResult = spawnSync(
        "diff",
        [
            "-u",
            "--label",
            `a/${path.relative(rootDir, file)}`,
            "--label",
            `b/${path.relative(rootDir, file)} (prettier)`,
            file,
            formattedFile,
        ],
        {
            encoding: "utf8",
        },
    );

    if (diffResult.stdout) {
        process.stderr.write(diffResult.stdout);
    }

    if (diffResult.stderr) {
        process.stderr.write(diffResult.stderr);
    }
}

function classifyMarkdownDiff(source, formatted) {
    const sourceLines = source.split("\n");
    const formattedLines = formatted.split("\n");
    const changedPairs = [];
    const maxLines = Math.max(sourceLines.length, formattedLines.length);

    for (let index = 0; index < maxLines; ++index) {
        const sourceLine = sourceLines[index] ?? "";
        const formattedLine = formattedLines[index] ?? "";

        if (sourceLine === formattedLine) {
            continue;
        }

        changedPairs.push([sourceLine, formattedLine]);
    }

    if (changedPairs.length === 0) {
        return null;
    }

    if (
        changedPairs.every(
            ([sourceLine, formattedLine]) =>
                isMarkdownTableLine(sourceLine) &&
                isMarkdownTableLine(formattedLine),
        )
    ) {
        return "Prettier a seulement réaligné un ou plusieurs tableaux Markdown. Le contenu n'est pas en erreur, c'est la mise en forme du tableau qui change.";
    }

    return null;
}

function isMarkdownTableLine(line) {
    return /^\s*\|.*\|\s*$/.test(line);
}
