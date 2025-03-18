# Guia de Uso do Sistema de Transporte Escolar

Este guia fornece instruções para evitar erros comuns ao trabalhar com o sistema. Siga essas orientações para garantir uma experiência sem problemas.

## Campos de Status

Vários modelos no sistema possuem campos de status com valores específicos:

### Motoristas e Monitores
- **Valores permitidos**: "Ativo", "Inativo", "Ferias", "Licenca"
- O sistema vai tentar converter valores como "true", "1", "active" para "Ativo" e "false", "0", "inactive" para "Inativo"
- **Dica**: Para maior consistência, use sempre os valores exatos permitidos

### Ônibus
- **Valores permitidos para status**: "Ativo", "Manutenção", "Inativo"

### Rotas, Alunos, Paradas e Horários
- O status é um campo booleano (true/false)
- **Dica**: Você pode usar true/false, 1/0, "Ativo"/"Inativo"

## Formatos de Data e Hora

- **Formato de data**: AAAA-MM-DD (exemplo: 2025-03-17)
- **Formato de hora**: HH:MM (exemplo: 08:30)
- O sistema tenta formatar automaticamente horas como 7:30 para 07:30
- **Dica**: Sempre use o formato 24h para horas

## Dependências Entre Entidades

Antes de criar registros, certifique-se de que as dependências existam:

1. **Para criar uma Viagem**, você precisa ter:
   - Uma Rota válida (rota_id)
   - Um Ônibus válido (onibus_id)
   - Um Motorista válido (motorista_id)
   - Um Horário válido (horario_id)
   - Um Monitor (opcional, monitor_id)

2. **Para criar uma Presença**, você precisa ter:
   - Uma Viagem válida (viagem_id)
   - Um Aluno válido (aluno_id)

3. **Para associar Paradas a Rotas**, você precisa ter:
   - Uma Rota válida (rota_id)
   - Uma Parada válida (parada_id)

## Exclusão de Registros

O sistema impede a exclusão de registros que possuem dependências:

- Não é possível excluir um **Ônibus** que tenha Viagens associadas
- Não é possível excluir uma **Rota** que tenha Viagens associadas
- Não é possível excluir um **Motorista/Monitor** que tenha Viagens associadas
- Não é possível excluir um **Aluno** que tenha Presenças associadas

**Solução**: Primeiro exclua os registros dependentes, depois a entidade principal.

## Campos Obrigatórios

### Motoristas
- nome, cpf, cnh, categoria_cnh, validade_cnh, telefone, endereco, data_contratacao

### Monitores
- nome, cpf, telefone, endereco, data_contratacao

### Ônibus
- placa, modelo, capacidade, ano_fabricacao, status

### Rotas
- nome (outros campos como descrição, origem, destino são opcionais)

### Alunos
- nome, data_nascimento, responsavel, telefone_responsavel, endereco

### Viagens
- data_viagem, rota_id, onibus_id, motorista_id, horario_id, hora_saida_prevista, status

## Cargo de Motoristas e Monitores

- **Valores permitidos**: "Efetivo", "ACT", "Temporário"
- O valor padrão é "Efetivo" se não for especificado

## Tipos de Paradas

- **Valores permitidos**: "Inicio", "Intermediaria", "Final"

## Relatórios

O sistema oferece relatórios em diferentes formatos:
- JSON (padrão via API)
- Excel (acessível via endpoints /excel)
- PDF (acessível via endpoints /pdf)

Para filtrar relatórios, use os parâmetros:
- data_inicio, data_fim (formato AAAA-MM-DD)
- rota_id, motorista_id, monitor_id, onibus_id
- status, cargo

## Dicas para Evitar Erros Comuns

1. **Sempre verifique** se as entidades relacionadas existem antes de criar registros
2. **Use os formatos corretos** para datas e horas
3. **Utilize os valores permitidos** para campos enumerated (status, cargo, tipo)
4. **Ao excluir registros**, certifique-se de que não existem dependências
5. **Em caso de erro**, verifique a mensagem de erro - o sistema fornece detalhes sobre o problema

Ao seguir essas diretrizes, você minimizará erros ao usar o sistema e garantirá a integridade dos dados.