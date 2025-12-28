.PHONY: structure structure-clean

structure:
tree -a --noreport . > STRUCTURE.txt

structure-clean:
tree -a --noreport . | grep -v '\.gitkeep' > STRUCTURE.clean.txt
