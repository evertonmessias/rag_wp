#!/bin/bash

# Script para commit e push no GitHub pessoal (branch única: main)

BRANCH="main"

cd "$(dirname "$0")" || exit 1

git status

echo "=== Sincronizando com o GitHub ==="

# No repositório novo o GitHub ainda não tem a branch: só faz pull se ela já existir lá
if git ls-remote --exit-code --heads origin "$BRANCH" >/dev/null 2>&1; then
    git pull origin "$BRANCH" || exit 1
else
    echo "Branch $BRANCH ainda não existe no GitHub (primeiro envio)."
fi

git add .

# Verifica se existe algo para commitar

if git diff --cached --quiet; then
    echo "Nenhuma alteração para commitar."
    echo "***** FEITO !!! *****"
    exit 0
fi

echo
echo "Arquivos que serão enviados:"
git status --short
echo

# Pergunta se deseja continuar

read -p "Deseja enviar para a branch ? (S/N) " resposta

if [[ ! "$resposta" =~ ^[Ss]$ ]]; then
    echo "Operação cancelada."
    echo "***** FEITO !!! *****"
    exit 0
fi

data=$(date +%d-%m-%Y_%H:%M:%S)

git commit -m "$data" || exit 1
git push -u origin "$BRANCH" || exit 1

echo
echo "Commit enviado para o GitHub com sucesso!"
echo "***** FEITO !!! *****"
